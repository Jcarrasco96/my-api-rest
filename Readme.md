# My API REST – Microframework PHP

![PHP](https://img.shields.io/static/v1?style=for-the-badge&message=PHP&color=777BB4&logo=php&logoColor=FFFFFF&label=)

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/Jcarrasco96/my-api-rest)

## 📝 Description
This project is a RESTful microframework written in **pure PHP**, designed for developing modern APIs in a simple, organized way without external dependencies. It includes dynamic controllers by convention, permission checking via attributes, access control, and request limits (`RateLimit`).

## 🚀 Features
- ✅ Versioned Controllers
- ✅ Dynamic Routing
- ✅ Custom Attributes
- ✅ Permission Checking and JWT Tokens
- ✅ Request Limiting
- ✅ Layer Separation
- ✅ No External Dependencies

## 📁 Project structure
```text
/attributes/
/core/
/exceptions/
/helpers/
/languages/
/models/
/services/
/validators/
composer.json
```

## 🔧 Requirements
- ✅ PHP >= 8.1
- ✅ Apache or Nginx server (friendly URLs)
- ✅ PDO extension enabled
- ✅ File system access (for rate limiting)

## ⚙️ Example of use
```php
#[RateLimit(limit: 10, seconds: 60)]
#[ControllerPermission(['?'])]
#[ControllerMethod(['POST'])]
public function actionLogin(): string
{
    // Login logic and token generation
}
```

## 📡 Example routes
```text
GET  /v1/user/index
POST /v1/user/create
GET  /v1/product/view/5
```
The URL automatically determines the controller (UserController) and method (actionIndex, actionCreate, etc.).

## 📥 Installation
1. Clone this repository
    ```text
    git clone https://github.com/Jcarrasco96/my-api-rest.git
    ```
2. In **composer.json** of main application merge:
    ```json
    {
      "require": {
        "jcarrasco96/my-api-rest": "1.0.*@dev"
      },
      "repositories": [
        {
          "type": "path",
          "url": "path\\to\\my-api-rest"
        }
      ]
    }
    ```
   
