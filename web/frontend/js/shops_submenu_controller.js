ShopsController.submenu = function() {
    const menuItems = [
        {
            label: 'All Items',
            action: () => ShopsController.read()
        },
        {
            label: 'Pending Items',
            action: () => ShopsController.read('', { status: 'pending' })
        },
        {
            label: 'Completed Items',
            action: () => ShopsController.read('', { status: 'completed' })
        },
        {
            label: 'Refresh',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => ShopsController.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};
