<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tamkulay Tiffins — Roles & Permissions Matrix
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | All permissions that should ever exist in the system.
    | Roles reference these by name. The seeder creates the union of this
    | list and everything mentioned in any role's permissions array.
    |--------------------------------------------------------------------------
    */
    'all_permissions' => [
        // User Management
        'user.view', 'user.create', 'user.edit', 'user.delete', 'user.assign-role',

        // Roles & Permissions
        'role.view', 'role.create', 'role.edit', 'role.delete', 'role.assign-permission',

        // Restaurant Settings
        'settings.view', 'settings.edit',

        // Menu
        'menu.view', 'menu.create', 'menu.edit', 'menu.delete', 'menu.publish',

        // Orders
        'order.view', 'order.update-status', 'order.cancel',

        // Payments
        'payment.view', 'payment.verify', 'payment.reject',

        // Wallet
        'wallet.view', 'wallet.credit', 'wallet.debit',

        // Inventory
        'inventory.view', 'inventory.adjust',

        // Suppliers
        'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete',

        // Purchases
        'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.receive',

        // Expenses
        'expense.view', 'expense.create', 'expense.edit', 'expense.delete',

        // Reports
        'report.view', 'report.export',

        // Audit Log
        'audit.view',
    ],

    'roles' => [

        'Admin' => [
            'label'       => 'Administrator',
            'description' => 'Full access to everything including users, roles, and settings.',
            'permissions' => ['*'],
        ],

        'Manager' => [
            'label'       => 'Manager',
            'description' => 'Day-to-day operations: orders, payments, menu, inventory, reports.',
            'permissions' => [
                'user.view', 'settings.view', 'role.view',

                'menu.view', 'menu.create', 'menu.edit', 'menu.delete', 'menu.publish',
                'order.view', 'order.update-status', 'order.cancel',
                'payment.view', 'payment.verify', 'payment.reject',
                'wallet.view', 'wallet.credit', 'wallet.debit',
                'inventory.view', 'inventory.adjust',
                'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete',
                'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.receive',
                'expense.view', 'expense.create', 'expense.edit', 'expense.delete',
                'report.view', 'report.export',
                'audit.view',
            ],
        ],

        'Kitchen Staff' => [
            'label'       => 'Kitchen Staff',
            'description' => 'View orders and mark them preparing / ready.',
            'permissions' => [
                'menu.view',
                'order.view', 'order.update-status',
                'inventory.view',
                'purchase.receive',
            ],
        ],

        'Delivery Staff' => [
            'label'       => 'Delivery Staff',
            'description' => 'View ready orders and mark them delivered.',
            'permissions' => [
                'order.view', 'order.update-status',
            ],
        ],

        'Customer' => [
            'label'       => 'Customer',
            'description' => 'Public-facing customer. Orders via the web app.',
            'permissions' => [],
        ],

    ],

    'permission_groups' => [
        'user'      => 'User Management',
        'role'      => 'Roles & Permissions',
        'settings'  => 'Restaurant Settings',
        'menu'      => 'Menu Management',
        'order'     => 'Order Management',
        'payment'   => 'Payment Verification',
        'wallet'    => 'Wallet Management',
        'inventory' => 'Inventory Management',
        'supplier'  => 'Supplier Management',
        'purchase'  => 'Purchasing & Receipt',
        'expense'   => 'Expense Management',
        'report'    => 'Reports',
        'audit'     => 'Audit Log',
    ],

];