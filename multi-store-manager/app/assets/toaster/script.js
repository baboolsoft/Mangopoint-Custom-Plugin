function genRandomString(length = 12, includeTime = true) {
    var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    var charLength = chars.length;
    var result = '';
    for (var i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * charLength));
    }
    return `${result}${includeTime ? Date.now() : ''}`;
}

function toaster({
    type = "success",
    text = null,
    delay = 5000,
    showClose = true,
    autoClose = true,
    callBack = false
}) {
    let navHeight = document.querySelector('#masthead').scrollHeight || 0;
    let id = genRandomString();
    let $ = jQuery;
    $('body').append(`<div id="${id}" class="toaster ${type}" style="top: ${navHeight}px">
        <div class="content">${text}
        ${showClose === true ? `<button class="close">
                <i class="fa fa-close"></i>
            </button>` : ""
        }
        </div>
    </div>`);
    setTimeout(() => {
        $('.toaster').addClass('show');
        if (showClose) {
            $(`#${id}`).find('.close').click(() => {
                closeToaster(id, callBack);
            });
        }
        if (autoClose) {
            setTimeout(() => {
                closeToaster(id, callBack);
            }, delay);
        }
    }, 100);
}

function closeToaster(id, callBack) {
    jQuery(`#${id}`).removeClass('show');
    if (typeof callBack === "function") {
        callBack();
    }
    setTimeout(() => {
        jQuery(`#${id}`).remove();
    }, 600);
}
