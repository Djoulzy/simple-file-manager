function list(tbody, allow_direct_link)
{
    var hashval = window.location.hash.substr(1);
    var $tbody = $(tbody);

    $.get('?do=list&file='+ hashval, function(data) {
        $tbody.empty();
        $('#breadcrumb').empty().html(renderBreadcrumbs(hashval));
        if(data.success) {
            $.each(data.results,function(k,v){
                $tbody.append(renderFileRow(v, allow_direct_link));
            });
            !data.results.length && $tbody.append('<tr><td class="empty" colspan=5>This folder is empty</td></tr>')
            data.is_writable ? $('body').removeClass('no_write') : $('body').addClass('no_write');
        } else {
            console.warn(data.error.msg);
        }
        $('#table').retablesort();
    },'json');
}

function renderFileRow(data, allow_direct_link) {
    var $link = $('<a class="name" />')
        .attr('href', data.is_dir ? '#' + encodeURIComponent(data.path) : './'+ encodeURIComponent(data.path))
        .text(data.name);
    if (!data.is_dir && !allow_direct_link)  $link.css('pointer-events','none');
    var $dl_link = $('<a/>').attr('href','?do=download&file='+ encodeURIComponent(data.path))
        .addClass('download').text('download');
    var $delete_link = $('<a href="#" />').attr('data-file',data.path).addClass('delete').text('delete');
    var perms = [];
    if(data.is_readable) perms.push('read');
    if(data.is_writable) perms.push('write');
    if(data.is_executable) perms.push('exec');
    var $html = $('<tr />')
        .addClass(data.is_dir ? 'is_dir' : '')
        .append( $('<td class="first" />').append($link) )
        .append( $('<td/>').attr('data-sort',data.is_dir ? -1 : data.size)
            .html($('<span class="size" />').text(formatFileSize(data.size))) )
        .append( $('<td/>').attr('data-sort',data.mtime).text(formatTimestamp(data.mtime)) )
        .append( $('<td/>').text(perms.join('+')) )
        .append( $('<td/>').append($dl_link).append( data.is_deleteable ? $delete_link : '') )
    return $html;
}

function renderBreadcrumbs(path) {
    var base = "",
        $html = $('<div/>').append( $('<a href=#>Home</a></div>') );
    $.each(path.split('%2F'),function(k,v){
        if(v) {
            var v_as_text = decodeURIComponent(v);
            $html.append( $('<span/>').text(' ▸ ') )
                .append( $('<a/>').attr('href','#'+base+v).text(v_as_text) );
            base += v + '%2F';
        }
    });
    return $html;
}

function formatTimestamp(unix_timestamp) {
    var m = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var d = new Date(unix_timestamp*1000);
    return [m[d.getMonth()],' ',d.getDate(),', ',d.getFullYear()," ",
        (d.getHours() % 12 || 12),":",(d.getMinutes() < 10 ? '0' : '')+d.getMinutes(),
        " ",d.getHours() >= 12 ? 'PM' : 'AM'].join('');
}

function formatFileSize(bytes) {
    var s = ['bytes', 'KB','MB','GB','TB','PB','EB'];
    for(var pos = 0;bytes >= 1000; pos++,bytes /= 1024);
    var d = Math.round(bytes*10);
    return pos ? [parseInt(d/10),".",d%10," ",s[pos]].join('') : bytes + ' bytes';
}