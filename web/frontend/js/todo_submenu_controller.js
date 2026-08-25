TodoController.submenu = function() {
    const menuItems = [
        {
            label: 'All Tasks',
            action: () => TodoController.read()
        },
        {
            label: 'Pending Tasks',
            action: () => TodoController.read('', { status: 'pending' })
        },
        {
            label: 'Completed Tasks',
            action: () => TodoController.read('', { status: 'completed' })
        },
        {
            label: 'Refresh',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => TodoController.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};