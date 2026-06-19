/* app_spinner.js */
app.spinner = function(){
    const spinner = document.getElementById('spinner');
    if (spinner) spinner.style.display = 'flex'; // Make it visible
};

app.clearSpinner = function(){
    const spinner = document.getElementById('spinner');
    if (spinner) spinner.style.display = 'none'; // Hide it again
};