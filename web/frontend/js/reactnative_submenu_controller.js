ReactnativeController.submenu = function() {
    const menuItems = [
        {
            label: 'All Items',
            action: () => ReactnativeController.read()
        },
        {
            label: 'Pending Items',
            action: () => ReactnativeController.read('', { status: 'pending' })
        },
        {
            label: 'Completed Items',
            action: () => ReactnativeController.read('', { status: 'completed' })
        },
        {
            label: 'Refresh',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => ReactnativeController.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};
