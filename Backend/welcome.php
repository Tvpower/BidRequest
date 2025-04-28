<?php
/**
 * BidRequest - Welcome Page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BidRequest API</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    h1 {
      color: #333;
      border-bottom: 2px solid #eee;
      padding-bottom: 10px;
    }
    .endpoint {
      background: #f4f4f4;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 5px;
    }
    .method {
      font-weight: bold;
      color: #0066cc;
    }
  </style>
</head>
<body>
<h1>Welcome to BidRequest API</h1>
<p>This is the API server for the BidRequest platform, a reverse e-commerce application.</p>

<h2>API Endpoints</h2>

<h3>Authentication</h3>
<div class="endpoint">
  <span class="method">POST</span> /api/auth/register - Register a new user
</div>
<div class="endpoint">
  <span class="method">POST</span> /api/auth/login - User login
</div>

<h3>Requests</h3>
<div class="endpoint">
  <span class="method">GET</span> /api/requests - List all requests
</div>
<div class="endpoint">
  <span class="method">POST</span> /api/requests - Create a new request
</div>
<div class="endpoint">
  <span class="method">GET</span> /api/requests/request/{id} - Get request details
</div>
<div class="endpoint">
  <span class="method">PUT</span> /api/requests/request/{id} - Update a request
</div>
<div class="endpoint">
  <span class="method">DELETE</span> /api/requests/request/{id} - Delete a request
</div>

<h3>Bids</h3>
<div class="endpoint">
  <span class="method">GET</span> /api/bids - List all bids
</div>
<div class="endpoint">
  <span class="method">POST</span> /api/bids - Create a new bid
</div>
<div class="endpoint">
  <span class="method">GET</span> /api/bids/bid/{id} - Get bid details
</div>
<div class="endpoint">
  <span class="method">PUT</span> /api/bids/bid/{id} - Update a bid
</div>
<div class="endpoint">
  <span class="method">DELETE</span> /api/bids/bid/{id} - Delete a bid
</div>
<div class="endpoint">
  <span class="method">POST</span> /api/bids/accept - Accept a bid
</div>

<h3>Categories</h3>
<div class="endpoint">
  <span class="method">GET</span> /api/categories - List all categories
</div>

<p>For more information, check the <a href="https://github.com/Tvpower/BidRequest">GitHub repository</a>.</p>
</body>
</html>
