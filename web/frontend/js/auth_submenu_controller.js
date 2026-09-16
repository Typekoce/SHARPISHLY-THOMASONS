AuthController.submenu = function() {
    const menuItems = [
        {
            label: 'All Items',
            action: () => AuthController.read()
        },
        {
            label: 'Pending Items',
            action: () => AuthController.read('', { status: 'pending' })
        },
        {
            label: 'Completed Items',
            action: () => AuthController.read('', { status: 'completed' })
        },
        {
            label: 'Refresh',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => AuthController.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};
