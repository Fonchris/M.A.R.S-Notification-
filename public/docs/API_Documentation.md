# Notification API Documentation

## Overview

The Notification API allows users to create, send, and check the status of notifications through various channels (Email, SMS, WhatsApp).

**Base URL**: `http://localhost:8000/`

## Endpoints

### 1. Create a New Notification

- **Endpoint**: `/api/notification/new`
- **Method**: `POST`
- **Request Body** (JSON):
    ```json
    {
        "userId": 1,
        "destination": "user@example.com",
        "smsDestination": "+15555555555",
        "whatsappDestination": "+15555555555",
        "message": "Your notification message",
        "mode": "email", // or "sms" or "whatsapp"
        "application": "applicationName"
    }
    ```
- **Responses**:
    - **201 Created**:
        ```json
        {
            "status code": 201,
            "id": 1,
            "message": "ok"
        }
        ```
    - **400 Bad Request**: 
        ```json
        {
            "message": "Missing parameters"
        }
        ```
    - **404 Not Found**: 
        ```json
        {
            "message": "User not found"
        }
        ```

### 2. Send Notification

- **Endpoint**: `/api/notification/send`
- **Method**: `POST`
- **Request Body** (JSON):
    ```json
    {
        "notificationId": 1
    }
    ```
- **Responses**:
    - **200 OK**:
        ```json
        {
            "message": "Notifications processed",
            "details": {
                "email": "Email sent successfully",
                "sms": "SMS sent successfully",
                "whatsapp": "WhatsApp message sent successfully"
            }
        }
        ```
    - **400 Bad Request**: 
        ```json
        {
            "message": "Notification ID is required"
        }
        ```
    - **404 Not Found**: 
        ```json
        {
            "message": "Notification not found"
        }
        ```

### 3. Get Notification Status

- **Endpoint**: `/api/notification/status`
- **Method**: `GET`
- **Query Parameters**:
    - `notificationId`: The ID of the notification to check.
- **Responses**:
    - **200 OK**:
        ```json
        {
            "id": 1,
            "userId": 1,
            "destination": "user@example.com",
            "message": "Your notification message",
            "status": "sent",
            "createdAt": "2023-01-01 12:00:00",
            "updatedAt": "2023-01-01 12:05:00",
            "mode": "email"
        }
        ```
    - **404 Not Found**: 
        ```json
        {
            "message": "Notification not found"
        }
        ```

## Status Codes

- **200 OK**: Successful request.
- **201 Created**: Resource successfully created.
- **400 Bad Request**: Invalid request parameters.
- **404 Not Found**: Resource not found.

## database configurations
- ** go to your .env file and chnage the url to your database
 in this format DATABASE_URL="postgresql://user:password@127.0.0.1:5432/databaseName?serverVersion=16&charset=utf8" 
 (insert  your user, password and databaseName )
 run the command to create the database:"php bin/console doctrine:database:create"
 create a migration :"php bin/console make:migration"
 apply the migration: "php bin/console doctrine:migrations:migrate"
 
## email configuration
-go your .env and add replace the placeholders
MAILER_DSN=smtp://youremail@example.com:yourEmailPassword@smtp.gmail.com:587

# Twilio configuration
-go your .env and add replace the placeholders
TWILIO_SID=yourSID
TWILIO_AUTH_TOKEN=yourAuthToken
TWILIO_PHONE_NUMBER=yourTwillioPhoneNumber
TWILIO_WHATSAPP_NUMBER=yourTwillioPhoneNumber


## process
- i started by changing my database url as i used mysql
then i ran the command and  created the database
- i created an entity for notification and another for users and did a many to one relationship between them
- i then made a migration and migrated it 
- after that i did the logic in the NotificationController.php


