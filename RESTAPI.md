# PureNAS REST API Documentation

## Authentication

If specified as non empty in config, all API requests requires an API key passed as query parameter: `?key=YOUR_API_KEY` at end. For example: `api/system/info?key=changeme`

## Base URL

```
/api/
```

## Endpoints

### Subscriber Management

**Get all subscribers**
```
GET /api/subscriber/getall
```

**Allow subscriber**
```
GET /api/subscriber/allow/172.16.0.4
```

**Disallow subscriber**
```
GET /api/subscriber/disallow/172.16.0.4
```

**Shape subscriber bandwidth**
```
GET /api/subscriber/shape/172.16.0.4/1000
GET /api/subscriber/shape/172.16.0.4/1000/500
```
Parameters: IP, download (kbit/s), upload (kbit/s, optional)

**Unshape subscriber**
```
GET /api/subscriber/unshape/172.16.0.4
```

### IP+MAC binding

**Add IP+MAC binding** (static ARP and/or FW_MACFIX)
```
GET /api/subscriber/mac/172.16.0.4/aa:bb:cc:dd:ee:ff
```

**Remove IP+MAC binding**
```
GET /api/subscriber/unmac/172.16.0.4
```

### IP Ban Management

**Ban IP or CIDR**
```
GET /api/ip/ban/1.2.3.4
GET /api/ip/ban/10.0.0.0/24
```

**Unban IP or CIDR**
```
GET /api/ip/unban/1.2.3.4
GET /api/ip/unban/10.0.0.0/24
```

### System Information

**Get system info**
```
GET /api/system/info
```

## Response Format

All responses are JSON. Success responses contain data, error responses contain `error` field.

## Example Responses

**subscriber/getall response:**
```json
{
  "172.16.0.4": {
    "ip": "172.16.0.4",
    "state": "ACTIVE",
    "mac": "aa:bb:cc:dd:ee:ff",
    "ratedown": "100 Mbit",
    "rateup": "50 Mbit",
    "hits": 1234
  }
}
```

**subscriber/allow Action response:**
```json
{
  "success": true,
  "output": "== Added 172.16.0.4 to allowed_clients set =="
}
```
