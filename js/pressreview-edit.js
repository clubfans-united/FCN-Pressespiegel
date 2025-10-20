require('jquery')

jQuery(function ($) {
  const ajaxUrl = window.pressreviewEdit?.ajaxUrl
  const ajaxNonce = window.pressreviewEdit?.ajaxNonce

  $('.page-title-action').after(
    `<button id="my-admin-button" class="page-title-action fcnp-import">
        <span class="button-text">Artikel aus Feeds importieren</span>
        <span class="spinner is-active" style="display: none"></span>
     </button>`
  )

  $('.fcnp-import').on('click', function (e) {
    e.preventDefault()

    const button = $(this)
    const spinner = button.find('.spinner')

    button.prop('disabled', true)
    spinner.show()

    $.ajax({
      url: ajaxUrl,
      type: 'GET',
      data: {
        _ajax_nonce: ajaxNonce,
        action: 'fcnp_import'
      },
      success: function (response) {
        console.log(response)
      },
      error: function (error) {
        console.error(error)
      },
      complete: function () {
        button.prop('disabled', false)
        spinner.hide()
      }
    })
  })
})
