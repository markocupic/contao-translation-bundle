document.addEventListener("DOMContentLoaded", function () {
  /**
   * Use ajax for links with the data-ajax-href attribute
   */
  let buttons = document.querySelectorAll('[data-ajax-href]');
  if (buttons) {
    buttons.forEach((button) => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        fetch(button.dataset.ajaxHref, {
          method: 'GET',
          headers: {
            'x-requested-with': 'XMLHttpRequest'
          },
        }).then(function (response) {
          return response.json();
        }).then(function (json) {
          if (json.status === 'success') {
            window.location.reload();
          }
        });
      });
    });
  }
});
