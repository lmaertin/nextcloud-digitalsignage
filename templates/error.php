<!DOCTYPE html>
<html>
<head>
  <title>Error - Digital Signage</title>
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f5f5f5;
    }
    .error-box {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      max-width: 400px;
      text-align: center;
    }
    h1 {
      color: #d32f2f;
      margin-bottom: 1rem;
    }
    p {
      color: #666;
    }
  </style>
</head>
<body>
  <div class="error-box">
    <h1>Access Denied</h1>
    <p><?php p($_['message']); ?></p>
  </div>
</body>
</html>
