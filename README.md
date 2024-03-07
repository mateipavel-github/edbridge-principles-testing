## Edbridge Principles Testing

Wrapper of the PrinciplesUS API written in laravel PHP with a sleek Nova admin backend and a simplistic UI for taking a PrinciplesUS test and viewing the results.

### Generating a tenant for the users to be created in

```
php artisan principles:create-tenant SimpleTestTenant
```

After running the command, a tenant UID will be returned which needs to be added to the .env file as the `PRINCIPLES_TENANT_UID`.

### Creating a Nova user

```
php artisan nova:user
```
