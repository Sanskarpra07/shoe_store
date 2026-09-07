# StepStyle - Shoe Store

A PHP + MySQL e-commerce shoe store (custom code, no framework). Features a storefront, customer accounts with OTP email verification, shopping cart, COD / eSewa / Khalti checkout, and a full admin panel.

---

## Requirements

- [XAMPP](https://www.apachefriends.org/) (Apache 2.4+, PHP 7.4+, MariaDB/MySQL) — Windows / Linux / macOS all supported
- PHP extensions (bundled with XAMPP):
  - `mysqli` — required (database)
  - `curl` — required for Khalti payments (falls back to sandbox if missing)
  - `mbstring` — recommended (used for product descriptions)

> The app detects its own URL (folder name, host and port) automatically, so it works no matter where you put it under `htdocs`.

---

## Tutorial: Run this project on a new computer

### Step 0 — Install XAMPP (one time)

1. Download **XAMPP** from https://www.apachefriends.org/ and install it.
   - Windows: install to `C:\xampp` (the default).
   - Linux: `sudo apt install xampp` or use the installer from the site.
   - macOS: use the `.dmg` installer.
2. Open the **XAMPP Control Panel** and press **Start** next to:
   - **Apache** — the web server
   - **MySQL** — the database server
3. Both should turn **green** (running). You can verify by opening http://localhost/ — you should see the XAMPP welcome page.

### Step 1 — Copy the project into `htdocs`

Get the project onto the new machine (USB, zip file, or `git clone`), then copy the **`shoe_store`** folder into your XAMPP web root:

| OS        | Web root location            |
|-----------|------------------------------|
| Windows   | `C:\xampp\htdocs\`           |
| Linux     | `/opt/lampp/htdocs/`         |
| macOS     | `/Applications/XAMPP/htdocs/`|

Example result on Windows:

```
C:\xampp\htdocs\shoe_store\            <- outer folder (can be renamed)
└── shoe_store\                        <- the project (this must keep its name)
    ├── index.php
    ├── install.php
    ├── setup.bat
    └── ...
```

Open the browser now:

```
http://localhost/shoe_store/shoe_store/
```

You will see an error like *"Connection Failed"* or *"Unknown database"* — that is normal, the database is not created yet. Go to **Step 2**.

### Step 2 — Install the database (choose ONE option)

**Option A — One click (Windows, recommended):**
Double-click **`setup.bat`** inside the `shoe_store` folder. It will:
1. Check that MySQL is running
2. Ask whether to reset any existing database
3. Create `shoe_store_db` and import the sample data
4. Open your store in the browser

**Option B — Web installer (any OS):**
Open the installer in your browser and press **"Save & run installation"**:

```
http://localhost/shoe_store/shoe_store/install.php
```

**Option C — Manual (command line):**
Open a terminal (Windows: `cmd`, Linux/Mac: terminal) and run:

```bash
# Windows (XAMPP default)
C:\xampp\mysql\bin\mysql.exe -u root < sql\setup.sql

# Linux / macOS
/opt/lampp/bin/mysql -u root < sql/setup.sql
```

> If your MySQL root user has a password, add `-p` and type it when prompted.

### Step 3 — Open the store

| Page              | URL                                                        |
|-------------------|------------------------------------------------------------|
| Storefront        | `http://localhost/shoe_store/shoe_store/index.php`         |
| Admin panel       | `http://localhost/shoe_store/shoe_store/admin/login.php`   |

### Default logins

| Role     | Username / Email    | Password      |
|----------|---------------------|---------------|
| **Admin**| `admin`             | `password`    |
| Staff    | `staff1`            | `password`    |
| Customer | `sita@example.com`  | `customer123` |

**Admin login** is at `/admin/login.php` (username + password).  
**Customer login** is at `/login.php` (email + password), then verify the 6-digit OTP shown on the page (demo mode — no real email is sent).

---

## What's inside

```
shoe_store/
├─ index.php            Storefront (home)
├─ shop.php             Product listing + filters (search / category / brand)
├─ product.php          Product detail + reviews
├─ cart.php             Shopping cart (session-based)
├─ checkout.php         Shipping form + payment method (COD / eSewa / Khalti)
├─ process_order.php    Order creation & payment routing
├─ order_success.php    Order confirmation
├─ track_order.php      Public order tracking (order ID + email)
├─ my_account.php       Customer profile / change password
├─ my_orders.php        Customer order history
├─ register.php         Customer signup (email OTP)
├─ login.php            Customer login
├─ verify_otp.php       OTP verification
├─ esewa_success.php    eSewa payment callback (success)
├─ esewa_failure.php    eSewa payment callback (failure)
├─ khalti_callback.php  Khalti payment callback
├─ install.php          One-click setup page (browser)
├─ setup.bat            One-click setup script (Windows)
├─ db.php               DB connection + CSRF helpers + base_url()
├─ db_config.php        DB connection settings  <-- edit / auto-written here
├─ payment_config.php   Payment gateway (sandbox) credentials
├─ sql/setup.sql        Database schema + sample data (source of truth)
└─ admin/               Admin panel (dashboard, products, categories, brands,
                        orders, reports, reviews, stock log, users)
```

## Payment gateways

Both payments run in **sandbox/test mode** (`payment_config.php`):
- **eSewa**: merchant code `EPAYTEST`, UAT gateway — no real money moves.
- **Khalti**: `test_secret_key`; if the sandbox API is unreachable, the order is simulated as successful.

Callback URLs (eSewa success/failure, Khalti callback) are generated automatically from the current site URL, so they work from any folder. Swap in live credentials in `payment_config.php` for production.

## Maintenance

- **Reset to fresh sample data**: `install.php` → *"Reset database to sample data"*, or re-run `setup.bat` and answer `Y`.
- **Change database settings**: edit `db_config.php`, or use the form in `install.php`.
- **Add your own products**: admin panel → *Products* → *Add Product*.
- **Track requests / debug**: Apache logs at `C:\xampp\apache\logs\error.log`.

## Troubleshooting

| Problem | Fix |
|---------|-----|
| *"Cannot connect to MySQL"* | Start MySQL in XAMPP Control Panel. If root has a password, set it in `db_config.php` or via the installer form. |
| Port 80 busy (Skype, IIS...) | Stop that service, or change Apache's port in `httpd.conf` and use the new port in the URL. |
| `setup.bat` says MySQL client not found | Make sure XAMPP is installed at `C:\xampp`, or pass `-p`. |
| Blank page / 500 error | Check `C:\xampp\apache\logs\error.log`; ensure `db_config.php` exists and the database name matches. |
| Re-import shows "Duplicate entry" | The database already has data — use the reset option instead of importing twice. |

## Security notes

This is a learning/demo build. Before putting it on the internet: add CSRF protection to admin actions and `add_to_cart.php`, use prepared statements in `admin/reports.php`, `cart.php`, `checkout.php` and `product.php` (they interpolate values into SQL), fix COD `payment_status` to `completed`, and validate stock before ordering.