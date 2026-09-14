LoginController.submenu = function() {
    const menuItems = [
        {
            label: 'All Items',
            action: () => LoginController.read()
        },
        {
            label: 'Pending Items',
            action: () => LoginController.read('', { status: 'pending' })
        },
        {
            label: 'Completed Items',
            action: () => LoginController.read('', { status: 'completed' })
        },
        {
            label: 'Refresh',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => LoginController.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};
