# My API REST – Microframework PHP

![PHP](https://img.shields.io/static/v1?style=for-the-badge&message=PHP&color=777BB4&logo=php&logoColor=FFFFFF&label=)

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/Jcarrasco96/my-api-rest)

## 📝 Description
This project is a RESTful microframework written in **pure PHP**, designed for developing modern APIs in a simple, organized way without external dependencies. It includes dynamic controllers by convention, permission checking via attributes, access control, and request limits (`RateLimit`).

## 🚀 Features
✅ Versioned Controllers  
✅ Dynamic Routing  
✅ Custom Attributes  
✅ Permission Checking and JWT  
✅ Request Limiting  
✅ Layer Separation  
✅ No External Dependencies

## 📁 Project structure
```text
/attributes/
/console/
/core/
/db/
/exceptions/
/languages/
/query/
/rest/
/validators/
composer.json
LICENSE.md
README.md (this file)
```

## 🔧 Requirements
✅ PHP >=8.2  
✅ Apache or Nginx server (friendly URLs)  
✅ PDO extension enabled  
✅ File system access (for logs and rate limiting)  

## ⚙️ Example of use of Rate Limit Checker
```php
#[RateLimit(limit: 10, seconds: 60)]
#[Route('auth/login', [Route::ROUTER_POST])]
public function actionLogin(): string
{
    // Login logic and token generation
}
```

## 📡 Example routes
```text
GET  /v1/user
POST /v1/user
GET  /v1/product/5
```
The URL automatically determines the controller and method.

## 📥 Installation
1. Clone this repository
    ```text
    git clone https://github.com/Jcarrasco96/my-api-rest.git
    ```
2. In **composer.json** of main application merge:
    ```json
    {
      "require": {
        "jcarrasco96/simple-api-rest": "1.0.*@dev"
      },
      "repositories": [
        {
          "type": "path",
          "url": "path\\to\\my-api-rest\\src"
        }
      ]
    }
    ```
3. In **index.php** of main application
   ```php
   require_once 'vendor/autoload.php';
   
   $config = require_once 'config/rest.php'; // config file
   
   (new Rest($config))->run();
   ```

## 🪤 Pull requests are welcome
