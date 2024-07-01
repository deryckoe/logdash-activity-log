const {render} = wp.element; //we are using wp.element here!
import './index.scss'

document.addEventListener('DOMContentLoaded', () => {
    expandEventDetails();
    resetLog();
});

function expandEventDetails() {


    const eventCells = document.querySelectorAll('.column-event_meta');

    if (eventCells) {

        eventCells.forEach((cell) => {

            const eventActions = cell.querySelector('.event_actions');
            const eventDetails = cell.querySelector('.event_details');

            if (eventActions) {

                const detailsBtn = eventActions.querySelector('.details');

                if (detailsBtn) {

                    detailsBtn.addEventListener('click', () => {

                        if (!detailsBtn.classList.contains('active')) {
                            detailsBtn.classList.add('active');
                            eventDetails.classList.add('active');
                        } else {
                            detailsBtn.classList.remove('active');
                            eventDetails.classList.remove('active');
                        }

                    })
                }

            }

        })

    }

}

function resetLog() {

    const reset_log_anchor = document.getElementById('logdash_reset_log');

    if (reset_log_anchor) {

        const message_element = document.getElementById('logdash_message');
        const message = document.createElement('p');

        reset_log_anchor.addEventListener('click', (e) => {

            e.preventDefault();

            if (confirm(reset_log_anchor.dataset.confirmMessage) === true) {

                const url = reset_log_anchor.getAttribute('href');

                fetch(url, {
                    method: 'post',
                    body: '',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                }).then((response) => {
                    return response.json()
                }).then((res) => {
                    if (res.status !== 'success') {
                        throw new Error(res.message);
                    } else {
                        displaySuccess(res.message);
                    }
                }).catch((error) => {
                    displayError(error);
                })
            }
        })
    }

    function displayMessage(message, type) {
        const message_element = document.getElementById('logdash_message');

        message_element.style.display = 'block';
        message_element.classList.remove('notice-error', 'notice-success', 'notice-warning', 'notice-info');
        message_element.classList.add(type);
        message_element.querySelector('p').innerHTML = message;
    }

    function displaySuccess(message) {
        console.log(message);
        displayMessage(message, 'notice-success');
    }

    function displayError(message) {
        displayMessage(message, 'notice-error');
    }

}


jQuery(document).ready(function($) {
    $('select.ld-select').select2();
});