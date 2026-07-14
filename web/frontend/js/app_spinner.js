/* app_spinner.js */
App.spinner = function(){
    const spinner = document.getElementById('spinner');
    if (spinner) spinner.style.display = 'flex'; // Make it visible
};

App.clearSpinner = function(){
    const spinner = document.getElementById('spinner');
    if (spinner) spinner.style.display = 'none'; // Hide it again
};