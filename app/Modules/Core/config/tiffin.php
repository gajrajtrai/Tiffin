<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tamkulay Tiffins — Roles & Permissions Matrix
    |--------------------------------------------------------------------------
    |
    | Centralized definition of every role and its permissions.
    | The seeder reads this file — update here to change the matrix.
    |
    */

    'roles' => [

        'Admin' => [
            'label'       => 'Administrator',
            'description' => 'Full access to everything including users, roles, and settings.',
            'permissions' => ['*'], // wildcard = all permissions
        ],

        'Manager' => [
            'label'       => 'Manager',
            'description' => 'Day-to-day operations: orders, payments, menu, inventory, reports.',
            'permissions' => [
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
				'user.view',
				'settings.view',
				'role.view',
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
            'permissions' => [], // no admin permissions — public UI only
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Groups (for UI display)
    |--------------------------------------------------------------------------
    */

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