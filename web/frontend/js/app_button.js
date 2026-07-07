/** app_button.js */
App.button = function(name,func){
    PageRegistry[name] = func;
};
