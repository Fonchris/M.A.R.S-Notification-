## clone my repository
- ** git clone https://github.com/Fonchris/M.A.R.S-Notification-.git

## database configurations
- ** go to your .env file and chnage the url to your database
 in this format DATABASE_URL="postgresql://user:password@127.0.0.1:5432/databaseName?serverVersion=16&charset=utf8" 
 (insert  your user, password and databaseName )
 run the command to create the database:"php bin/console doctrine:database:create"
 create a migration :"php bin/console make:migration"
 apply the migration: "php bin/console doctrine:migrations:migrate"
 
## email configuration
-go to your .env and add replace the placeholders
MAILER_DSN=smtp://youremail@example.com:yourEmailPassword@smtp.gmail.com:587

# Twilio configuration
-go to your .env and add replace the placeholders
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
