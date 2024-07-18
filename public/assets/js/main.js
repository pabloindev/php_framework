/*(function($){
	
}(jQuery));*/

// Utility function to get an element by its ID
function getById(id) {
    return document.getElementById(id);
}

// Utility function to add an event listener to all elements selected by a given selector
function addEventListenerToAll(selector, event, handler) {
    const elements = document.querySelectorAll(selector);
    if (elements.length === 0) {
        console.warn(`No elements found for selector: ${selector}`);
        return;
    }
    elements.forEach(element => {
        element.addEventListener(event, handler);
    });
}

document.addEventListener("DOMContentLoaded", function(){
	
});