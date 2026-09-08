NotificationsController.submenu = function() {
    const menuItems = [
        {
            label: 'All Items',
            action: () => NotificationsController.read()
        },
        {
            label: 'Pending Items',
            action: () => NotificationsController.read('', { status: 'pending' })
        },
        {
            label: 'Completed Items',
            action: () => NotificationsController.read('', { status: 'completed' })
        },
        {
            label: 'Refresh',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => NotificationsController.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};
