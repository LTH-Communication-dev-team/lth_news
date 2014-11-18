$(document).ready(function () {
    pid = $('#lth_news_pid').val();
    $('#sortable').sortable({
            revert       : true,
            stop         : function(event,ui){ 
                //console.log(ui);
                uid = ui.item.context.id;
                //nextSibling = ui.item.context.nextElementSibling.firstChild.id;
                if(ui.item.context.previousElementSibling) {
                    where = ui.item.context.previousElementSibling.firstChild.id;
                } else {
                    where = 'first';
                }
                lth_news_ajax('updateIndex','pages',JSON.stringify({"uid":uid,"pid":pid,"where":where}));
    }
    }).disableSelection();
    
    //$( "#sortable" ).sortable();
    $( "#sortable" );
});
        
function lth_news_ajax(action,table,query)
{
    $.ajax({
        type : "POST",
        url : 'index.php',
        data: {
            eID : 'tx_lthnews_pi1',
            action : action,
            table : table,
            query : query,
            sid : Math.random()
        },
        //dataType: "json",
        success: function(data) {
            console.log(data);
        },
        error: function(xhr, status, error) {
            var err = eval("(" + xhr.responseText + ")");
            console.log(err);
        },
        complete: function(data) {
            //console.log('complete'+data.table);
        },
        failure: function(errMsg) {
            console.log(errMsg);
        }
    });
}
        
function lth_news_ajax_tce(id,where,pid)
{
    $.ajax({
        type : "GET",
        url : 'typo3/tce_db.php?cmd[pages]['+id+'][move]='+where,
        //contentType: "application/json; charset=utf-8",
        //dataType: "json",
        success: function(data) {
            lth_news_ajax('updateIndex','pages',pid);
        },
        error: function(xhr, status, error) {
            //var err = eval("(" + xhr.responseText + ")");
            alert(status+error);
        },
        complete: function(data) {
            console.log(data);
        },
        failure: function(errMsg) {
            console.log(errMsg);
        }
    });
}

//window.location.href='tce_db.php?cmd[pages][1294][move]=1294