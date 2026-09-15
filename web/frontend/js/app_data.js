/** 
 * Application data
 * Store global data
 * */

App.data = {};

App.setData = function(key,value){
    App.data[key]=value;
};

App.getData = function(key){
    return App.data[key]
};