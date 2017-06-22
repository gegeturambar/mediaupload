
var Utils = {};
Utils.types = ["fail","success","notice"];

Utils.hideAllMsg = function(){
    if(this.types) {
        this.types.forEach(function (item) {
            Utils.hideMsg(item);
        });
    }
}


Utils.showMsg = function(msg,type,html){
    html === undefined ? false : html;
    this.hideAllMsg();
    let selector = '#msg_'+type;

    if(html){
        $(selector).html(msg);
    }else {
        $(selector).text(msg);
    }
    selector = '#div_msg_'+type;
    $(selector).removeClass('hide');
    $(selector).show();
    this.movetoAnchor("div_msg_"+type);
}

Utils.hideMsg = function(type){
    let selector = '#msg_'+type;
    $(selector).text("");
    selector = '#div_msg_'+type;
    $(selector).addClass('hide');
    $(selector).hide();
}

Utils.movetoAnchor = function(anchor){
    anchor = "#"+anchor;
    //yInitPos = $(window).scrollTop();

    // On ajoute le hash dans l'url.
    //window.location.hash = anchor;

    // Comme il est possible que l'ajout du hash perturbe le défilement, on va forcer le scrollTop à son endroit inital.
    //$(window).scrollTop(yInitPos);

    // On cible manuellement l'ancre pour en extraire sa position.
    // Si c'est un ID on l'obtient.
    target = ($(anchor + ":first"));

    // Sinon on cherche l'ancre dans le name d'un a.
    if (target.length == 0) {
        target = ($("a[name=" + anchor.replace(/#/gi,"") + "]:first"))
    }

    // Si on a trouvé un name ou un id, on défile.
    if (target.length == 1) {
        yPos = target.offset().top; // Position de l'ancre.

        // On anime le défilement jusqu'à l'ancre.
        $('html,body').animate({scrollTop: yPos - 40}, 1000); // On décale de 40 pixels l'affichage pour ne pas coller le bord haut de l'affichage du navigateur et on défile en 1 seconde jusqu'à l'ancre.
    }
}

jQuery(document).ready(function(){

    $("#mail").change(function () {
        Utils.hideAllMsg();
        let str = $(this).val();
        var regex = /^[\w|.]*@s2hgroup.com$/;
        res = regex.test(str);
        if(!res) {
            Utils.showMsg("Mail non compatible","fail");
        }
    });

    $("#password").change(function () {
        let str = $(this).val();
        var regex = /^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[\W])\S*$/;
        res = regex.test(str);
        if(!res) {
            Utils.showMsg("Mot de passe non compatible","fail");
        }
    });

});