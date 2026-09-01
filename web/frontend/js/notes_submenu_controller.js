NotesController.submenu = function() {
    const menuItems = [
        {
            label: 'All Items',
            action: () => NotesController.read()
        },
        {
            label: 'Pending Items',
            action: () => NotesController.read('', { status: 'pending' })
        },
        {
            label: 'Completed Items',
            action: () => NotesController.read('', { status: 'completed' })
        },
        {
            label: 'Refresh',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => NotesController.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};
