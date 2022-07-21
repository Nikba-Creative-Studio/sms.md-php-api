# SMS.MD API SDK
#### Send SMS using sms.md

ServiceSMS messaging in Moldova - Build a reliable and effective connection with customers using the capabilities of sms.md

## Installation

SmsMd requires [PHP](https://php.net/) v5.4+ to run.

Require this package with Composer in the root directory of your project.

```sh
composer require nikba/sms.md-php-api
```

## Usage
Create a new instance with your API key:
```sh
$sms = new \Nikba\SmsMdPhpApi\SmsMd('API_TOKEN');
```

### Send Single sms message
Parameters:
* Phone Number
* Message
* Sender alias 
```sh
$sms->send("37360820825", "Hello World!", "Nikba Creative Studio");
```

### Get Balance
```sh
$sms->getBalance();
```

### Get Messages List
Parameters:
* Page
* Date From (01.07.2022)
* Date To (20.07.2022)
* Status (1-Pending, 2-Sent, 3-Delivered, 9-Error)
```sh
$sms->getMessages(1,"01.07.2022", "20.07.2022", "2");
```

### Get Message by id
Parameters:
* id
```sh
$sms->getMessage("449d5410-82d3-4b6e-96bc-cc92a33eb3f5");
```

### Get Messages Statuses
```sh
$sms->getMessageStatuses();
```
**Server response**

```sh
[
  {
    "id": 1,
    "name": "Ждет отправки",
    "description": "Отложенная отправка"
  },
  {
    "id": 2,
    "name": "Отправлено",
    "description": "Отправлено оператору"
  },
  {
    "id": 3,
    "name": "Доставлено",
    "description": "Доставдено оператором"
  },
  {
    "id": 4,
    "name": "Повторная отправка",
    "description": "Ошибка при отпрвке, ошибка позволяет отправить еще раз"
  },
  {
    "id": 5,
    "name": "У оператора",
    "description": "У оператора в очереди"
  },
  {
    "id": 9,
    "name": "Ошибка отправки",
    "description": "Не отправлено оператором"
  }
]
```

### Get all Contacts
Parameters:
* Page
```sh
$sms->getContacts(1);
```

### Get all address books
Parameters:
* Page
```sh
$sms->getAddressBooks(1);
```

### Get all address book contacts
Parameters:
* Id
* Page
```sh
$sms->getAddressBookContacts("449d5410-82d3-4b6e-96bc-cc92a33eb3f5", 1);
```

### Get all sender aliases
```sh
$sms->getSenderAliases();
```

### Get Stats
```sh
$sms->getStats();
```

## License

MIT
**Free Software, Hell Yeah!**
