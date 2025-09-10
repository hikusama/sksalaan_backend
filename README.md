# Laravel x RestApi's


![alt](icon.png)

 

  <div align="center">
    <p>👁️ Visitor Count</p>
    <a href="https://hits.sh/github.com/hikusama/sksalaan_backend/" target="_blank">
      <img 
        src="https://hits.sh/github.com/hikusama/sksalaan_backend/sksalaan_backend.svg?style=flat-square&label=Visitors&color=00cc88&labelColor=222222" 
        alt="Visitor Count" />
    </a>
  </div>

## Setup
**Step 1:** Install packages
```bash
composer install
```

**Step 2:** Configuring 
1. Environment
```bash
cp .env.example .env
```
2. Generate key
```bash
php artisan key:generate
```
3. Migrate tables
```bash
php artisan migrate:f
```
4. Connection
- Get the IPv4
```bash
ipconfig
```
- Editing the .env, rename APP_URL value
`http://localhost:8000` to `http://yourcopiedIP:8000`
- tThen add your copied IP in SANCTUM_STATEFUL_DOMAINS 

**Step 3:** Run the server 
```bash
php artisan serve --host=0.0.0.0 --port=8000
```



