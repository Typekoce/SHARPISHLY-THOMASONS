/** snapshots_controller.js */
const SnapshotsController = {
    async request(){
        app.spinner();
        try {
            //
        } catch(e){
            app.flash(msg);
        }
    },
    createForm(){
        const fields = { 
            'title': {'type':'text'}, 
            'desscription': {'type':'text'}
        };
         app.eForm(fields);
    }
};