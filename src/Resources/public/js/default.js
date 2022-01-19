document.addEventListener("DOMContentLoaded", function () {
  /**
   * Use ajax for links with the data-ajax-href attribute
   */
  let buttons = document.querySelectorAll('[data-ajax-href]');
  if (buttons) {
    buttons.forEach((button) => {
      button.addEventListener('click', (e) => {
        e.preventDefault();

        var data = new FormData();
        data.append('authToken', ContaoTranslator);

        fetch(button.dataset.ajaxHref, {
          method: 'POST',
          headers: {
            'x-requested-with': 'XMLHttpRequest'
          },
          body: data
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
