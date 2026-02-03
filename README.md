# ESP32 SMS Server – Laravel API (SIM900A Gateway)

## Overview

This project provides a **multi-device SMS Gateway** based on **ESP32 + SIM900A**, controlled by a **Laravel backend secured with Laravel Sanctum**.

Each ESP32 device acts as an independent GSM gateway:

* polls the backend for SMS to send
* sends SMS via the GSM module
* reports send results (sent / failed)
* forwards received SMS to the server
* sends a detailed heartbeat (GSM, Wi-Fi, uptime, counters)

The Laravel backend includes:

* a REST API for ESP32 devices
* multi-device management
* Inbox / Outbox storage
* heartbeat history
* an admin dashboard for monitoring and control

---

## Implemented Features

### ESP32

* Poll SMS Outbox
* Forward received SMS (Inbox)
* Send result reporting (sent / failed with error)
* Heartbeat (GSM, Wi-Fi, uptime, statistics)
* Optional Serial debugging
* LCD I2C live status display

### Laravel Backend

* Sanctum authentication (auth:sanctum)
* Multi-device support (multiple ESP32 devices on the same API)
* Inbox and Outbox management
* Heartbeat history (device_statuses)
* Admin dashboard:

    * device list and details
    * inbox and outbox
    * heartbeat charts
    * device activity monitoring

---

## Architecture and Data Flow

```
ESP32 + SIM900A
|
|-- GET  /api/devices/{device_uid}/outbox
|       Fetch queued SMS
|
|-- POST /api/devices/{device_uid}/outbox/{sms_id}/result
|       Report send result
|
|-- POST /api/devices/{device_uid}/inbox
|       Forward received SMS
|
|-- POST /api/devices/{device_uid}/heartbeat
|       GSM and Wi-Fi supervision
```

Each ESP32 device is identified by a unique `device_uid`.

---

## Authentication (Laravel Sanctum)

All API routes under `/api/*` are protected by Sanctum.

Required headers:

```
Authorization: Bearer <API_TOKEN>
Accept: application/json
Content-Type: application/json
```

---

## Generate an API Token for a Device (Artisan Command)

The project includes a custom Artisan command to generate a Sanctum token for a device.

### Command

```bash
php artisan device:token esp32-001 1
```

### Parameters

| Parameter | Description                              |
| --------- | ---------------------------------------- |
| esp32-001 | device_uid (must exist in devices table) |
| 1         | user_id (defaults to 1 if omitted)       |

### Behavior

* Finds the device by device_uid
* Generates a Sanctum token for the user
* Token name is set to `device:{device_uid}`
* Token is printed to the console

Important notes:

* The token belongs to the user, not technically bound to the device
* Device validation is enforced through the device_uid in API routes
* No expiration logic is implemented by default

Example output:

```text
TOKEN (Bearer):
MW0L5Vcoe8Ix5VubhsSZAfLkMhEeaUoDirnGHcsr5232dfb5
```

Use this token in the ESP32 firmware.

---

## API Endpoints (Based on Actual Code)

### Fetch SMS Outbox

GET

```
/api/devices/{device_uid}/outbox?limit=5
```

Rules:

* Only SMS with status `queued`
* FIFO order (id ASC)
* limit clamped between 1 and 20

Response:

```json
{
  "data": [
    {
      "id": 12,
      "to": "+33600000000",
      "message": "Test SMS"
    }
  ]
}
```

---

### Report SMS Send Result

POST

```
/api/devices/{device_uid}/outbox/{sms_id}/result
```

Body:

```json
{
  "status": "sent",
  "error": null
}
```

Rules:

* status: sent or failed
* error stored if failed
* sent_at automatically set when successful

Response:

```json
{ "ok": true }
```

---

### Forward Received SMS (Inbox)

POST

```
/api/devices/{device_uid}/inbox
```

Body:

```json
{
  "from": "+212600000000",
  "message": "Hello",
  "received_at": 123456
}
```

Notes:

* received_at is optional (ESP32 timestamp)
* server stores received_at as current time

Response:

```json
{ "ok": true }
```

---

### Heartbeat / Device Supervision

POST

```
/api/devices/{device_uid}/heartbeat
```

Body example:

```json
{
  "uptime_s": 12345,
  "wifi_rssi": -55,
  "sent_count": 10,
  "recv_count": 3,
  "gsm": {
    "rssi_raw": 20,
    "ber": 0,
    "dbm": -85,
    "signal_percent": 65,
    "operator": "Orange",
    "sim_status": "READY",
    "creg_stat": 1,
    "roaming": false,
    "iccid": "8933...",
    "imsi": "20801..."
  }
}
```

Effects:

* Inserts a row into device_statuses
* Updates cached fields in devices
* Updates last_seen_at

Response:

```json
{ "ok": true }
```

---

## Admin Dashboard (Web)

Accessible after login.

Main routes:

```
/admin
/admin/devices
/admin/devices/{id}
/admin/outbox
/admin/inbox
```

Features:

* manage multiple ESP32 devices
* view device details and last status
* view heartbeat history
* manage inbox and outbox
* per-device SMS monitoring

Access is protected by an admin middleware using the `is_admin` flag.

---

## Database Models Summary

### devices

* device_uid (unique)
* name, is_active
* cached GSM and Wi-Fi fields
* last_seen_at

### device_statuses

* heartbeat history (one row per heartbeat)

### sms_outboxes

* device_id, to, message
* status (queued, sent, failed)
* error, sent_at

### sms_inboxes

* device_id, from, message, received_at

---

## Laravel Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Create a user via `/register`, then log in to access the dashboard.

Create devices via:

```
/admin/devices/create
```

Generate a token:

```bash
php artisan device:token esp32-001 1
```

---

## ESP32 Installation

### Available Sketches

The ESP32 folder contains two sketches:

#### ESP32_Code.ino (Production)

* Runtime version for normal operation
* Minimal or no Serial output
* Recommended for long-term gateway usage

#### ESP32_Code_With_debug.ino (Debug)

* Same logic and API behavior
* Detailed Serial logs for diagnostics
* Used to debug GSM, LCD, Wi-Fi, or API issues

---

### ESP32 Configuration

```cpp
const char* WIFI_SSID  = "...";
const char* WIFI_PASS  = "...";
const char* API_BASE   = "http://<SERVER_IP_OR_DOMAIN>";
const char* DEVICE_ID  = "esp32-001";
const char* API_TOKEN  = "<SANCTUM_TOKEN>";
```

---

### SIM900A GSM Connection

* RX: GPIO16
* TX: GPIO17
* Baudrate: 9600

SIM900A requires a stable 4.0–4.2V power supply with peaks up to 2A.
Common ground between ESP32 and SIM900A is mandatory.

---

## LCD Display (I2C 16x2)

### Wiring (typical ESP32)

* VCC to 5V or 3.3V (depending on module)
* GND to GND
* SDA to GPIO21
* SCL to GPIO22

Common I2C addresses: 0x27 or 0x3F.

---

### LCD Display Format

Line 1:

```
GSM:65% OP:Orange
```

Line 2:

```
S:10 R:3 Wi:OK
```

* GSM signal strength in percent
* Operator name
* SMS sent and received counters
* Wi-Fi connection status

LCD refreshes approximately every second.

---

### LCD Diagnostics

If the LCD does not display correctly:

* use ESP32_Code_With_debug.ino
* check Serial Monitor for I2C initialization logs
* verify power and I2C address

---

## Quick API Tests

```bash
curl -H "Authorization: Bearer <TOKEN>" \
"http://localhost:8000/api/devices/esp32-001/outbox?limit=5"
```

```bash
curl -X POST \
-H "Authorization: Bearer <TOKEN>" \
-H "Content-Type: application/json" \
-d '{"from":"+33600000000","message":"test"}' \
"http://localhost:8000/api/devices/esp32-001/inbox"
```

---

## License

MIT License

---

## Author

Mouad Elouafi
GitHub: [https://github.com/elouafidev](https://github.com/elouafidev)
