App.goFetch = function(){

    try {

        data = await fetch(App.url('foo/bar'));
        
    } catch(e){

    }

};