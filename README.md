# Event Scheduling Management System

A web-based system for scheduling events at the Institute of Management and Technology (IMT), Enugu.
It stops venue clashes, notifies attendees by SMS and e-mail, and keeps records of events, attendance and feedback.

**Built with:** HTML, CSS, JavaScript/jQuery, PHP 8 and MySQL/MariaDB.

---

## Setting it up on Kali Linux

### Option A: Apache (recommended for the defence)

```bash
# 1. Install the web server, PHP and the database
sudo apt update
sudo apt install -y apache2 php php-mysql php-curl mariadb-server unzip

# 2. Start the services
sudo systemctl start mariadb apache2

# 3. Put the project in Apache's web folder
sudo unzip ~/Downloads/event-scheduler.zip -d /var/www/html/
cd /var/www/html/event-scheduler

# 4. Create the config file
sudo cp config.sample.php config.php

# 5. Create the database, the app's database user and the demo data
sudo mariadb < database/eventdb.sql

# 6. Let Apache save uploaded passport photos
sudo chown -R www-data:www-data uploads
```

Open **http://localhost/event-scheduler** in the browser.

### Option B: PHP's built-in server (no Apache)

```bash
sudo apt install -y php php-mysql php-curl mariadb-server unzip
sudo systemctl start mariadb
unzip ~/Downloads/event-scheduler.zip -d ~/
cd ~/event-scheduler
cp config.sample.php config.php
sudo mariadb < database/eventdb.sql
php -S localhost:8000
```

Open **http://localhost:8000** and leave the terminal open while you use it.

---

## Demo accounts

Log in with the **phone number** and password.

| Role  | Name           | Phone       | Password  | Security answer |
|-------|----------------|-------------|-----------|-----------------|
| Admin | Admin Officer  | 08030000001 | Admin@123 | enugu           |
| User  | Chinedu Okafor | 08030000002 | User@123  | enugu           |
| User  | Ngozi Eze      | 08030000003 | User@123  | enugu           |
| User  | Emeka Nwankwo  | 08030000004 | User@123  | enugu           |
| User  | Adaeze Obi     | 08030000005 | User@123  | enugu (inactive until the admin activates it) |

To register a new account during the demo, use one of the unused staff IDs:
**IMT/STF/0106** to **IMT/STF/0110**.

### Reset the demo data

Run this before the defence. The demo events are dated relative to the day you import, so they always show as upcoming.

```bash
sudo mariadb < database/eventdb.sql
```

---

## Hosting on GitHub

GitHub can host two things for this project:

- **A project page (GitHub Pages):** the `docs/` folder holds a page describing the system, with screenshots and the demo accounts. GitHub Pages only serves fixed pages, so the page can't run the system itself. It is live at https://thisisbigmike.github.io/event-scheduler/ and is published from the `gh-pages` branch, which is a copy of `docs/`. After changing anything in `docs/`, update the live page with `git push origin $(git subtree split --prefix docs):gh-pages --force`.
- **The live system (GitHub Codespaces):** [![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/thisisbigmike/event-scheduler) Click the badge and create the codespace. The first build takes a few minutes, then the system starts on port 80. Open the **Ports** tab and click the globe icon next to port 80. To share the link, right-click the port and set **Port Visibility** to **Public**. The link works only while the codespace is running; it stops after 30 minutes without activity. Free GitHub accounts get a monthly allowance of Codespaces hours.

## Deploy online with Render (free, one click)

The project includes a `Dockerfile` that runs Apache, PHP and MariaDB together, and a `render.yaml` blueprint, so it deploys to [Render](https://render.com) without any setup.

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/thisisbigmike/event-scheduler)

1. Click the button above, and sign in to Render with your GitHub account.
2. Click **Deploy Blueprint**. The first build takes a few minutes.
3. Open the address Render gives you (for example `https://imt-event-scheduler.onrender.com`) and log in with the demo accounts.

On the free plan:

- The site goes to sleep after 15 minutes without visitors, and the next visit takes about a minute to wake it.
- The demo data is reloaded every time the site starts, so changes made online don't last. That keeps the demo clean.
- It runs in demo mode: SMS and e-mail are logged on the Notifications page, not sent. Real e-mail can't be switched on there: Render's free plan blocks outgoing e-mail ports (25, 465 and 587).
- Anyone with the link can log in as the admin, because the demo passwords are printed in this public README. Until the next restart they can change or delete data. Don't enter real staff details on the online demo.

To run the same container on your own computer: `docker build -t imt-event-scheduler .` then `docker run -p 8080:80 imt-event-scheduler`, and open http://localhost:8080.

## Sharing a temporary public link from your laptop

After setting up with Apache (Option A), you can give anyone a public https link to the system. It works only while the laptop is on and the tunnel command is running.

```bash
cd /var/www/html/event-scheduler

# 1. Stop the private files (config, SQL file, includes/) being reachable from the internet
sudo cp apache/event-scheduler.conf /etc/apache2/conf-available/
sudo a2enconf event-scheduler
sudo systemctl reload apache2

# 2. Install Cloudflare's free tunnel tool (no account needed)
wget https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared-linux-amd64.deb

# 3. Start the tunnel and leave this terminal open
cloudflared tunnel --url http://localhost:80
```

It prints an address like `https://some-random-words.trycloudflare.com`. Add `/event-scheduler/` to the end and share that link. Press Ctrl+C to stop sharing. You get a new address every time you start it.

Anyone with the link can log in with the demo accounts, so run `sudo mariadb < database/eventdb.sql` afterwards to reset the data.

## Putting it online (free PHP + MySQL hosting)

To get a link that stays up, put the system on a free PHP + MySQL host such as InfinityFree. You need your own account there; the names of buttons can differ slightly between hosts.

1. Sign up and create a free website. You get an address such as `yourname.infinityfreeapp.com`.
2. In the control panel, open **MySQL Databases** and create a database. Write down the four details it shows: host name (e.g. `sql123.infinityfree.com`), database name, user name and password.
3. Open **phpMyAdmin** for that database, click **Import** and choose `database/eventdb-hosting.sql`. Use this file, not `eventdb.sql`: shared hosts don't allow `CREATE DATABASE`.
4. Open the **File Manager** (or connect by FTP), go into `htdocs`, and upload everything inside the `event-scheduler` folder (not the folder itself).
5. In `htdocs`, copy `config.sample.php` to `config.php` and put the four database details from step 2 into `db_host`, `db_user`, `db_pass` and `db_name`.
6. Open your site address. New free sites can take a few minutes to start working.

Keep `demo_mode` set to `true` on free hosting: free plans usually block outgoing SMS and e-mail connections.

## Turning on real SMS and e-mail

The system starts in **demo mode**. Messages are saved on the Notifications page instead of being sent, so the demo works without internet or SMS credit.

To send for real, edit `config.php` on your own computer or server. Never do this on the public Render demo: there, `config.php` is built from `config.sample.php`, so your keys would end up in the public repository, and anyone with the published admin password could send SMS on your credit. On any server that sends real messages, change the admin password first.

```php
'demo_mode'     => false,
'sms_provider'  => 'termii',          // or 'bulksmsnigeria'
'sms_api_key'   => 'YOUR_API_KEY',
'sms_sender_id' => 'IMTEvents',       // must be approved on your gateway account

'email_from' => 'yourname@gmail.com',
'smtp_user'  => 'yourname@gmail.com',
'smtp_pass'  => 'your-gmail-app-password',  // a Gmail App Password, not your normal password
```

Never put API keys in any other file, and never share `config.php`.
If a message can't be sent (no internet, no credit, wrong key), it's still saved in the log with the status **failed**.

## Automatic reminders

Reminders go to attendees of events starting within the next 24 hours.
The admin can press **Send due reminders** on the dashboard, or a cron job can send them automatically:

```bash
crontab -e
# add this line (runs every 30 minutes):
*/30 * * * * php /var/www/html/event-scheduler/cron_reminders.php
```

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| "config.php not found" | `cp config.sample.php config.php` |
| "Could not connect to the database" | Start MariaDB (`sudo systemctl start mariadb`) and import `database/eventdb.sql` |
| Photo upload fails on registration | `sudo chown -R www-data:www-data uploads` (Apache), or check your user can write to `uploads/` |
| Blank page or error when sending real SMS | Install cURL for PHP: `sudo apt install php-curl`, then `sudo systemctl restart apache2` |
| Page not found at localhost/event-scheduler | Check the folder is `/var/www/html/event-scheduler` and Apache is running |

---

## Files

| File | What it does |
|------|--------------|
| `index.php` | Login page (phone number + password) |
| `register.php` | Registration: staff ID check, passport photo, security question |
| `logout.php` | Ends the session |
| `profile.php` | View profile, change password |
| `user_dashboard.php` | A user's upcoming and past events |
| `user_event.php` | Event details and feedback form |
| `admin_dashboard.php` | Admin home: statistics, upcoming events, pending accounts |
| `admin_events.php` | List of all events |
| `admin_event_form.php` | Create or edit an event, choose attendees, **venue clash check** |
| `check_clash.php` | AJAX endpoint for the live clash warning |
| `admin_notify.php` | Security question, then SMS + e-mail to attendees |
| `admin_reminders.php` | Send reminders for events starting soon |
| `cron_reminders.php` | Same as above, for a cron job |
| `admin_users.php` | Activate or deactivate accounts |
| `admin_venues.php` | Add or delete venues |
| `admin_reports.php` | Events by venue and date, venue usage, attendees |
| `admin_notifications.php` | Log of every SMS and e-mail |
| `includes/db.php` | Database connection and prepared-statement helpers |
| `includes/auth.php` | Sessions, login checks, CSRF protection, output escaping |
| `includes/functions.php` | Venue clash check, validation, message text |
| `includes/notify.php` | SMS gateway, e-mail (SMTP), reminders |
| `includes/header.php`, `footer.php` | Shared page layout and menu |
| `assets/css/style.css` | Styles |
| `assets/js/app.js` | jQuery: live clash check, select-all, photo preview |
| `database/eventdb.sql` | Database tables and demo data (creates the database and its user) |
| `database/eventdb-hosting.sql` | Same tables and demo data, for shared hosting (import in phpMyAdmin) |
| `config.sample.php` | Settings template (copy to `config.php`) |
| `apache/event-scheduler.conf` | Apache settings that block private files when the site is public |
| `Dockerfile`, `docker/` | All-in-one container: Apache + PHP + MariaDB, loads the demo data on start |
| `render.yaml` | Render blueprint for the one-click free deploy |
| `.devcontainer/devcontainer.json` | Runs the live system in GitHub Codespaces |
| `docs/` | Project page for GitHub Pages |

## Database tables

| Table | Purpose |
|-------|---------|
| `register` | User accounts (admin and staff) |
| `event` | Scheduled events |
| `country` | Venues (name kept from the design document) |
| `staff` | Valid IMT staff IDs allowed to register |
| `event_attendee` | Which users are invited to which event, and their feedback |
| `notification_log` | Every SMS and e-mail sent or attempted |

## How the venue clash check works

Two bookings at the same venue on the same date clash when:

```
existing start < new end   AND   existing end > new start
```

So if Senate Chamber is booked 10:00–12:00, a new booking at 11:00–13:00 is rejected, but 12:00–14:00 is allowed.
Cancelled events don't hold the venue. The check runs live in the form (jQuery + `check_clash.php`) and again on the server when saving, inside a transaction that locks the venue row so two admins can't take the same slot at once.
See `find_venue_clash()` in `includes/functions.php`.
