/*
  ESP32 + SIM900A + LCD I2C 16x2
  - Line 1: GSM:<percent>% OP:<operator>
  - Line 2: S:<sent> R:<recv> Wi:<OK/NO>

  API (Laravel):
    GET  /api/devices/{deviceId}/outbox?limit=5
    POST /api/devices/{deviceId}/outbox/{smsId}/result
    POST /api/devices/{deviceId}/inbox
    POST /api/devices/{deviceId}/heartbeat

  Notes:
  - SIM900A needs a strong 4.0-4.2V supply (peaks ~2A) + common GND with ESP32.
  - UART2 pins: RX=GPIO16, TX=GPIO17 by default here.
*/

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <Preferences.h>

// =====================
// CONFIG
// =====================
const char* WIFI_SSID  = "elouafi.dev";
const char* WIFI_PASS  = "SFR11112020M";

const char* API_BASE   = "http://192.168.1.200"; // e.g. http://192.168.1.50
const char* DEVICE_ID  = "esp32-001";
const char* API_TOKEN  = "MW0L5Vcoe8Ix5VubhsSZAfLkMhEeaUoDirnGHcsr5232dfb5";

// LCD I2C address (commonly 0x27 or 0x3F)
#define LCD_ADDR 0x27
#define LCD_COLS 16
#define LCD_ROWS 2

// ESP32 I2C pins
#define I2C_SDA 21
#define I2C_SCL 22

// SIM900A UART2 pins
#define UART_GSM_RX 16
#define UART_GSM_TX 17
#define GSM_BAUD    9600

// Timers
const uint32_t POLL_OUTBOX_MS  = 4000;
const uint32_t HEARTBEAT_MS    = 15000;
const uint32_t LCD_REFRESH_MS  = 1000;
const uint32_t WIFI_RETRY_MS   = 15000;

// =====================
// GLOBALS
// =====================
HardwareSerial GSM(2);
LiquidCrystal_I2C lcd(LCD_ADDR, LCD_COLS, LCD_ROWS);
Preferences prefs;

uint32_t sentCount = 0;
uint32_t recvCount = 0;

unsigned long lastPoll = 0;
unsigned long lastHb   = 0;
unsigned long lastLcd  = 0;
unsigned long lastWifiAttempt = 0;

struct GsmNetInfo {
  int rssi = 99;          // 0..31, 99 unknown
  int ber  = -1;
  int dbm  = 0;           // computed
  int percent = -1;       // 0..100, -1 unknown
  String operatorName;    // ex: "Orange F"
  String simStatus;       // READY / SIM PIN / NOT INSERTED ...
  int cregStat = -1;      // 0..5
  bool roaming = false;
  String iccid;           // optional
  String imsi;            // optional
};

GsmNetInfo lastNet; // cached for LCD

// =====================
// HELPERS: GSM SERIAL READ
// =====================
String gsmReadLine(uint32_t timeoutMs = 1500) {
  unsigned long start = millis();
  String line;
  while (millis() - start < timeoutMs) {
    while (GSM.available()) {
      char c = GSM.read();
      if (c == '\r') continue;
      if (c == '\n') {
        if (line.length()) return line;
      } else {
        line += c;
      }
    }
    delay(2);
  }
  return line;
}

bool gsmCmd(const String& cmd, const String& expect, uint32_t timeoutMs = 2000) {
  if (cmd.length()) GSM.println(cmd);
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    String line = gsmReadLine(timeoutMs);
    if (line.length()) {
      if (line.indexOf(expect) >= 0) return true;
      if (line.indexOf("ERROR") >= 0) return false;
    }
  }
  return false;
}

bool gsmQueryLine(const String& cmd, const String& startsWith, String &outLine, uint32_t timeoutMs = 2000) {
  GSM.println(cmd);
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    String line = gsmReadLine(timeoutMs);
    if (line.startsWith(startsWith)) { outLine = line; return true; }
    if (line.indexOf("ERROR") >= 0) return false;
  }
  return false;
}

// =====================
// GSM NET METRICS
// =====================
int csqToDbm(int rssi) {
  if (rssi == 99 || rssi < 0) return 0;
  if (rssi == 0) return -113;
  if (rssi == 1) return -111;
  if (rssi >= 2 && rssi <= 30) return -109 + 2 * (rssi - 2);
  if (rssi == 31) return -51;
  return 0;
}

int csqToPercent(int rssi) {
  if (rssi == 99 || rssi < 0) return -1;
  int p = (int) lround((rssi / 31.0) * 100.0);
  if (p < 0) p = 0;
  if (p > 100) p = 100;
  return p;
}

GsmNetInfo readGsmNetInfo() {
  GsmNetInfo info;

  // CSQ
  {
    String line;
    if (gsmQueryLine("AT+CSQ", "+CSQ:", line, 1500)) {
      int colon = line.indexOf(':');
      int comma = line.indexOf(',', colon + 1);
      if (comma > 0) {
        info.rssi = line.substring(colon + 1, comma).toInt();
        info.ber  = line.substring(comma + 1).toInt();
        info.dbm = csqToDbm(info.rssi);
        info.percent = csqToPercent(info.rssi);
      }
    }
  }

  // Operator name
  {
    String line;
    if (gsmQueryLine("AT+COPS?", "+COPS:", line, 2500)) {
      int q1 = line.indexOf('"');
      int q2 = line.indexOf('"', q1 + 1);
      if (q1 > 0 && q2 > q1) info.operatorName = line.substring(q1 + 1, q2);
    }
  }

  // SIM status
  {
    String line;
    if (gsmQueryLine("AT+CPIN?", "+CPIN:", line, 1500)) {
      int colon = line.indexOf(':');
      info.simStatus = line.substring(colon + 1);
      info.simStatus.trim();
    }
  }

  // Network registration
  {
    String line;
    if (gsmQueryLine("AT+CREG?", "+CREG:", line, 1500)) {
      int comma = line.indexOf(',');
      if (comma > 0) {
        info.cregStat = line.substring(comma + 1).toInt();
        info.roaming = (info.cregStat == 5);
      }
    }
  }

  // Optional ICCID (AT+CCID)
  {
    GSM.println("AT+CCID");
    unsigned long start = millis();
    while (millis() - start < 1500) {
      String line = gsmReadLine(1500);
      if (line.length() >= 10 && line != "OK" && line.indexOf("+") < 0) {
        bool digitsOnly = true;
        for (size_t i=0;i<line.length();i++){
          if (!isDigit(line[i])) { digitsOnly = false; break; }
        }
        if (digitsOnly) info.iccid = line;
      }
      if (line == "OK") break;
    }
  }

  // Optional IMSI (AT+CIMI)
  {
    GSM.println("AT+CIMI");
    unsigned long start = millis();
    while (millis() - start < 1500) {
      String line = gsmReadLine(1500);
      if (line.length() >= 10 && line != "OK" && line.indexOf("+") < 0) {
        bool digitsOnly = true;
        for (size_t i=0;i<line.length();i++){
          if (!isDigit(line[i])) { digitsOnly = false; break; }
        }
        if (digitsOnly) info.imsi = line;
      }
      if (line == "OK") break;
    }
  }

  return info;
}

// =====================
// WIFI/HTTP HELPERS
// =====================
void wifiConnectNonBlocking() {
  if (WiFi.status() == WL_CONNECTED) return;
  if (millis() - lastWifiAttempt < WIFI_RETRY_MS) return;

  lastWifiAttempt = millis();
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
}

bool httpGetJson(const String& url, DynamicJsonDocument& doc) {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  http.begin(url);
  http.addHeader("Authorization", String("Bearer ") + API_TOKEN);

  int code = http.GET();
  if (code <= 0) { http.end(); return false; }
  String body = http.getString();
  http.end();

  DeserializationError err = deserializeJson(doc, body);
  return !err;
}

bool httpPostJson(const String& url, const JsonDocument& payload) {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", String("Bearer ") + API_TOKEN);

  String out;
  serializeJson(payload, out);

  int code = http.POST(out);
  http.end();
  return (code >= 200 && code < 300);
}

// =====================
// LCD RENDER (exact requirement)
// Line 1: GSM:<xx>% OP:<op>   (only first line contains percent + operator)
// Line 2: S:<sent> R:<recv> Wi:<OK/NO>
// =====================
String fit16(const String& s) {
  if (s.length() == 16) return s;
  if (s.length() > 16) return s.substring(0,16);
  String t = s;
  while (t.length() < 16) t += " ";
  return t;
}

String shortOperator(const String& op) {
  // Keep it short for 16 chars line:
  // We'll allow up to 7 chars for operator after "OP:"
  if (!op.length()) return "----";
  String s = op;
  s.replace("France", "");
  s.replace("F", "");
  s.trim();
  if (s.length() > 7) s = s.substring(0, 7);
  if (!s.length()) s = "OP";
  return s;
}

void lcdDraw() {
  // Line 1 format: "GSM:xx% OP:xxxxx"
  String sig = (lastNet.percent >= 0) ? String(lastNet.percent) : String("--");
  if (sig.length() == 1) sig = "0" + sig; // 0..9 => 0x
  String op = shortOperator(lastNet.operatorName);

  String l1 = "GSM:" + sig + "% OP:" + op;
  // ensure 16
  l1 = fit16(l1);

  // Line 2 format: "S:xxx R:xxx Wi:OK"
  String wi = (WiFi.status() == WL_CONNECTED) ? "OK" : "NO";
  String l2 = "S:" + String(sentCount) + " R:" + String(recvCount) + " Wi:" + wi;
  l2 = fit16(l2);

  lcd.setCursor(0,0); lcd.print(l1);
  lcd.setCursor(0,1); lcd.print(l2);
}

// =====================
// GSM INIT + SMS SEND/RECV
// =====================
bool gsmInit() {
  for (int i=0;i<8;i++) {
    if (gsmCmd("AT", "OK", 1000)) break;
    delay(300);
  }
  if (!gsmCmd("ATE0", "OK", 1200)) return false;            // echo off
  if (!gsmCmd("AT+CMGF=1", "OK", 1200)) return false;        // text mode
  gsmCmd("AT+CSCS=\"GSM\"", "OK", 1500);                     // charset
  gsmCmd("AT+CNMI=2,1,0,0,0", "OK", 1500);                   // new SMS indications
  return true;
}

bool gsmSendSms(const String& number, const String& message) {
  GSM.print("AT+CMGS=\"");
  GSM.print(number);
  GSM.println("\"");
  delay(200);

  // wait prompt '>'
  unsigned long start = millis();
  bool gotPrompt = false;
  while (millis() - start < 3000) {
    if (GSM.available()) {
      char c = GSM.read();
      if (c == '>') { gotPrompt = true; break; }
    }
    delay(5);
  }
  if (!gotPrompt) return false;

  GSM.print(message);
  GSM.write(26); // Ctrl+Z

  start = millis();
  while (millis() - start < 20000) {
    String line = gsmReadLine(2000);
    if (line.indexOf("OK") >= 0) return true;
    if (line.indexOf("ERROR") >= 0) return false;
  }
  return false;
}

void reportOutboxResult(const String& smsId, bool ok, const String& err) {
  DynamicJsonDocument payload(512);
  payload["status"] = ok ? "sent" : "failed";
  payload["error"]  = ok ? "" : err;

  String url = String(API_BASE) + "/api/devices/" + DEVICE_ID + "/outbox/" + smsId + "/result";
  httpPostJson(url, payload);
}

void pollOutboxAndSend() {
  if (WiFi.status() != WL_CONNECTED) return;

  DynamicJsonDocument doc(8192);
  String url = String(API_BASE) + "/api/devices/" + DEVICE_ID + "/outbox?limit=5";

  if (!httpGetJson(url, doc)) return;

  JsonArray items = doc["data"].as<JsonArray>();
  for (JsonVariant v : items) {
    String smsId = v["id"].as<String>();
    String to    = v["to"].as<String>();
    String msg   = v["message"].as<String>();

    // Optional: ensure SIM READY + registered before sending
    bool ok = gsmSendSms(to, msg);

    reportOutboxResult(smsId, ok, ok ? "" : "gsm_send_failed");

    if (ok) {
      sentCount++;
      prefs.putUInt("sent", sentCount);
    }

    delay(300);
  }
}

// Handle incoming +CMTI indications
void handleIncomingSms() {
  if (!GSM.available()) return;

  String line = gsmReadLine(200);
  if (!line.length()) return;

  if (line.startsWith("+CMTI:")) {
    // +CMTI: "SM",<index>
    int comma = line.indexOf(',');
    if (comma < 0) return;
    int idx = line.substring(comma + 1).toInt();
    if (idx <= 0) return;

    // read SMS
    GSM.print("AT+CMGR=");
    GSM.println(idx);

    String from, body;
    unsigned long start = millis();
    while (millis() - start < 4000) {
      String l = gsmReadLine(1500);
      if (l.startsWith("+CMGR:")) {
        // Parse sender
        int q1 = l.indexOf("\",\"");
        if (q1 > 0) {
          int q2 = l.indexOf("\"", q1 + 3);
          if (q2 > q1) from = l.substring(q1 + 3, q2);
        }
      } else if (l == "OK") {
        break;
      } else if (l.length() && l.indexOf("+") != 0) {
        // message body line
        body = l;
      }
    }

    bool posted = false;
    if (from.length() && body.length() && WiFi.status() == WL_CONNECTED) {
      DynamicJsonDocument payload(1024);
      payload["from"] = from;
      payload["message"] = body;
      payload["received_at"] = (uint64_t)(millis() / 1000);

      String postUrl = String(API_BASE) + "/api/devices/" + DEVICE_ID + "/inbox";
      posted = httpPostJson(postUrl, payload);
    }

    // increment local counter even if server is down? Here we increment only if parsed.
    if (from.length() && body.length()) {
      recvCount++;
      prefs.putUInt("recv", recvCount);
    }

    // delete sms to avoid memory full
    GSM.print("AT+CMGD=");
    GSM.println(idx);
    gsmCmd("", "OK", 1500);
  }
}

// =====================
// HEARTBEAT
// =====================
void sendHeartbeat() {
  if (WiFi.status() != WL_CONNECTED) return;

  lastNet = readGsmNetInfo();

  DynamicJsonDocument payload(2048);
  payload["uptime_s"] = (uint32_t)(millis() / 1000);
  payload["wifi_rssi"] = WiFi.RSSI();
  payload["sent_count"] = sentCount;
  payload["recv_count"] = recvCount;

  JsonObject gsm = payload.createNestedObject("gsm");
  gsm["rssi_raw"] = lastNet.rssi;
  gsm["ber"] = lastNet.ber;
  gsm["dbm"] = lastNet.dbm;
  gsm["creg_stat"] = lastNet.cregStat;
  gsm["roaming"] = lastNet.roaming;

  if (lastNet.percent >= 0) gsm["signal_percent"] = lastNet.percent;

  if (lastNet.operatorName.length()) gsm["operator"] = lastNet.operatorName;
  if (lastNet.simStatus.length())    gsm["sim_status"] = lastNet.simStatus;
  if (lastNet.iccid.length())        gsm["iccid"] = lastNet.iccid;
  if (lastNet.imsi.length())         gsm["imsi"]  = lastNet.imsi;

  String url = String(API_BASE) + "/api/devices/" + DEVICE_ID + "/heartbeat";
  httpPostJson(url, payload);
}


// =====================
// SETUP / LOOP
// =====================
void setup() {
  Serial.begin(115200);

  // I2C + LCD
  Wire.begin(I2C_SDA, I2C_SCL);
  lcd.init();
  lcd.backlight();

  // Load counters
  prefs.begin("smsbox", false);
  sentCount = prefs.getUInt("sent", 0);
  recvCount = prefs.getUInt("recv", 0);

  // GSM UART
  GSM.begin(GSM_BAUD, SERIAL_8N1, UART_GSM_RX, UART_GSM_TX);

  // Boot display (your requirement):
  // Line1 = GSM percent + operator (we'll display placeholders until we read net)
  // Line2 = sent/recv info
  lastNet.percent = -1;
  lastNet.operatorName = "";
  lcd.clear();
  lcdDraw();

  // WiFi connect (non-blocking approach)
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  lastWifiAttempt = millis();

  // Init GSM
  lcd.setCursor(0,0); lcd.print(fit16("GSM:--% OP:BOOT"));
  lcd.setCursor(0,1); lcd.print(fit16("S:"+String(sentCount)+" R:"+String(recvCount)+" Wi:--"));

  bool ok = gsmInit();
  if (!ok) {
    // show GSM fail but keep loop running (maybe power issue)
    lastNet.percent = -1;
    lastNet.operatorName = "GSMFAIL";
  } else {
    // first net read for immediate LCD
    lastNet = readGsmNetInfo();
  }

  lcdDraw(); // ensure display matches requested layout immediately
}

void loop() {
  // Keep WiFi trying without blocking
  wifiConnectNonBlocking();

  // Handle incoming SMS notifications
  handleIncomingSms();

  // Poll outbox
  if (millis() - lastPoll > POLL_OUTBOX_MS) {
    lastPoll = millis();
    pollOutboxAndSend();
  }

  // Heartbeat + refresh cached net info
  if (millis() - lastHb > HEARTBEAT_MS) {
    lastHb = millis();
    sendHeartbeat();  // also updates lastNet
    // If WiFi down, still refresh local net info for LCD
    if (WiFi.status() != WL_CONNECTED) lastNet = readGsmNetInfo();
  }

  // LCD refresh (always uses the requested format)
  if (millis() - lastLcd > LCD_REFRESH_MS) {
    lastLcd = millis();
    // refresh net info periodically for operator/signal updates even between heartbeats
    // (lightweight: only every LCD refresh would be too frequent for COPS; keep it controlled)
    // We'll refresh CSQ more often and COPS less often:
    static unsigned long lastQuickSig = 0;
    static unsigned long lastOpRead   = 0;

    if (millis() - lastQuickSig > 2000) {
      lastQuickSig = millis();
      // quick CSQ update only
      String line;
      if (gsmQueryLine("AT+CSQ", "+CSQ:", line, 1500)) {
        int colon = line.indexOf(':');
        int comma = line.indexOf(',', colon + 1);
        if (comma > 0) {
          int rssi = line.substring(colon + 1, comma).toInt();
          lastNet.rssi = rssi;
          lastNet.dbm = csqToDbm(rssi);
          lastNet.percent = csqToPercent(rssi);
        }
      }
    }

    if (millis() - lastOpRead > 30000) {
      lastOpRead = millis();
      // slower operator read
      GsmNetInfo tmp = readGsmNetInfo();
      // keep counters unchanged; only copy net fields
      lastNet = tmp;
    }

    lcdDraw();
  }

  delay(10);
}
