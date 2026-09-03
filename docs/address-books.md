# Address books & contacts

[← Back to docs index](README.md)

Scopes: reading needs `contacts:read`; creating/editing/importing/deleting needs
`contacts:write`.

## Address books

```php
// Create
$book = $sms->createAddressBook('Newsletter subscribers', 'From the website form');
$id = $book['id'];

// Update
$sms->updateAddressBook($id, 'Newsletter', 'Renamed');

// List (paginated: ['data' => [...], 'meta' => [...]])
$sms->listAddressBooks(page: 1);

// Fetch one
$sms->getAddressBook($id);

// Delete
$sms->deleteAddressBook($id);
```

## List contacts of a book

Returns `['data' => [...], 'meta' => [...]]`.

```php
$page = $sms->listAddressBookContacts($id, ['firstName' => 'Ion'], page: 1);
```

### Contact filters

| Key | Match |
|---|---|
| `phoneNumber` | Numbers **containing** the value |
| `firstName` | First names **containing** the value |
| `lastName` | Exact match (use `lastName[like]` for partial) |
| `status` | `1` Active, `2` Block, `3` BlackList, `4` … |

## Import contacts

One contact per line: `number[,firstName[,lastName[,birthday]]]`. A line that
isn't a valid phone number is **skipped**, not rejected.

```php
$result = $sms->importContacts($id, "69123456,Ion,Popescu\n69654321\n", [
    'deduplicate'       => true,  // skip numbers already in this book (default)
    'onlyNational'      => false, // keep only Moldovan numbers
    'onlyInternational' => false, // keep only foreign numbers
]);

$result['imported']; // count added
$result['skipped'];  // count skipped, with reasons
```

Filtering (`onlyNational` / `onlyInternational`) is applied **before**
deduplication, so a filtered-out number is never also counted as a duplicate.

## Delete a contact

```php
$sms->deleteContact('CONTACT_ID');
```

## See also

- [Error handling](errors.md)
