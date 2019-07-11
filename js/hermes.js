jQuery(document).ready(function ($) {   

    $('.frmChatRead').ajaxForm({
        type: 'post',
        success: function (response) {
            console.log(response);
        },
        error: function (response) {
            console.log(response);
        }
    });

    $('.frmChatSend').ajaxForm({
        type: 'post',
        success: function (response) {          
            console.log(response);          
        },
        error: function (response) {
            console.log(response);
        }
    });

    $('.frmEmail').ajaxForm({
        type: 'post',
        success: function (response) {
            console.log(response);
        },
        error: function (response) {
            console.log(response);
        }
    });

    $('.frmEnemy').ajaxForm({
        type: 'post',
        success: function (response) {
            console.log(response);
        },
        error: function (response) {
            console.log(response);
        }
    });

    $('.frmFriend').ajaxForm({
        type: 'post',
        success: function (response) {
            console.log(response);
        },
        error: function (response) {
            console.log(response);
        }
    });    
   
    //$(".history").animate({ scrollTop: $('.history').prop("scrollHeight")}, 1000);   
});

function showDiv(uniqueId) {
    document.getElementById(uniqueId).style.display = 'block';
}

function hideDiv(uniqueId) {
    document.getElementById(uniqueId).style.display = 'none';
}