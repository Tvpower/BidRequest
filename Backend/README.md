# BidRequest Backend: Your Project's API Companion 🚀
Hey there! Welcome to the BidRequest Backend setup guide. This walkthrough will help you get your project's API up and running smoothly.

## Getting Started: What You'll Need 🛠️
Before we dive in, make sure you have:
- PHP 7.4 or newer (with PDO MySQL support)
- A MySQL or MariaDB database
- Apache web server with mod_rewrite
- Composer (optional, but handy for future additions)

## Project Layout: Finding Your Way Around 🗺️
Here's how we've organized the backend:
``` 
bidrequest-backend/
├── api/                # Where the magic happens
│   ├── auth/           # Login and registration
│   ├── requests/       # Manage project requests
│   ├── bids/           # Bid-related operations
│   └── categories/     # Category management
├── config/             # Configuration files
├── .env                # Secret settings
└── .htaccess           # Server configuration
```
## Let's Set This Up! 🔧
### Step 1: Get the Code
Simply clone or extract the repository into your web server's directory.
### Step 2: Configure Your Environment
Create a `.env` file with your database details. It'll look something like this:
``` 
DB_HOST=your-database-host
DB_PORT=your-port
DB_NAME=your-database-name
DB_USER=your-username
DB_PASSWORD=your-password

APP_URL=http://localhost/bidrequest
APP_DEBUG=true
```
### Step 3: Database Setup
- Connect to your MySQL database
- Run the SQL in `database-schema.sql` to create tables

### Step 4: Apache Configuration
Make sure the `.htaccess` file is in place. No extra steps needed for most setups!
### Step 5: Test Drive 🏎️
Navigate to `http://localhost/bidrequest/api/categories/` in your browser. You should see a JSON response with categories.
## API Playground 🎮
### Authentication
- **Register:** Send a POST to `/api/auth/register.php`
- **Login:** POST to `/api/auth/login.php`

### Requests
- **List Requests:** GET `/api/requests/`
- **Create Request:** POST to `/api/requests/`
- **Update/Delete:** PUT/DELETE to `/api/requests/request.php?id=1`

### Bids
- **List Bids:** GET `/api/bids/`
- **Create Bid:** POST to `/api/bids/`
- **Accept Bid:** POST to `/api/bids/accept.php`

## Pro Tips & Warnings 🚨
1. This is a demo implementation. For production, beef up your security!
2. Keep your `.env` file out of public view
3. Use HTTPS in production
4. Implement rate limiting
5. Always validate user inputs

## Connecting with Angular 🔗
Your Angular frontend will talk to these endpoints. Quick checklist:
- Set API base URL
- Create an HTTP interceptor
- Develop models matching API responses
- Build services to interact with the API

## Stuck? Let's Troubleshoot 🛠️
- **CORS Issues:** Check your headers
- **Database Connection:** Verify `.env` credentials
- **404 Errors:** Confirm mod_rewrite is active
- **Server Errors:** Peek at the PHP error log

## Final Thoughts
This setup is your launchpad. Customize, expand, and make it your own!
