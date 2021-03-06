///
// Navigator
///
$(function() {
    $('#side .tab a').on({
        'click' : function(e) {
            e.preventDefault();
            var idx = $(this).parents('li').index();
            $('#side .tab > li').eq(idx).addClass('on').siblings().removeClass('on');
            $('#gnb .menu').eq(idx).stop().fadeIn(100).siblings().hide();
        }
    });
    $('#gnb .menu > li > a').on({
        'click' : function(e) {
            e.preventDefault();
            $(this).next().stop().slideToggle(100).parents('li').toggleClass('on');
        }
    });
    $('#gnb .menu > li').each(function() {
        if ($(this).hasClass('active')) {
            $(this).find('a').click();
        }
    });
});

///
// Navigator active
//
$(function() {
    var href = window.document.location.href;
    href = href.replace(PH_DOMAIN, '', href);

    if (href.indexOf('?') !== -1) {
        href = PH_DIR + href.replace(PH_DOMAIN, '', href);
        href = href.replace(href.substr(href.indexOf('?')), '' ,href);
    }
    if (href.indexOf('#') !== -1) {
        href = PH_DIR + href.replace(PH_DOMAIN, '', href);
        href = href.replace(href.substr(href.indexOf('#')), '' ,href);
    }
    if (href.indexOf('/manage/mod') !== -1) {
        $('#side .tab a[data-tab="mod"]').click();
    }

    if ($('#side #gnb .menu a[href="' + href + '"]').length > 0){
        $('#side #gnb .menu a[href="' + href + '"]')
        .closest('li').addClass('on')
        .closest('ul').prev('a').click();

    } else {
        $('#side #gnb .menu a[data-idx-href]').each(function() {
            var idx_href = $(this).data('idx-href');
            idx_href = idx_href.split('|');
            for (var i=0; i < idx_href.length; i++) {
                if (href.indexOf(idx_href[i]) != -1) {
                    $(this)
                    .closest('li').addClass('on')
                    .closest('ul').prev('a').click();
                }
            }

        })
    }

});

///
// label active
///
function label_active() {
    $('label.__label').each(function() {
        $this = $(this);
        if ($this.find('input').is(':checked')) {
            $this.addClass('active');
        } else {
            $this.removeClass('active');
        }
    });
}
$(window).on({
    'load' : label_active
});
$(function() {
    $('label.__label').on({
        'click' : function() {
            label_active();
        }
    });
});

///
// Orderby
///
function get_query() {
    var url = document.location.href;
    var qs = url.substring(url.indexOf('?') + 1).split('&');
    for (var i = 0, result = {}; i < qs.length; i++) {
        qs[i] = qs[i].split('=');
        result[qs[i][0]] = decodeURIComponent(qs[i][1]);
    }
    return result;
}

$(function(){
    var qry = get_query();

    $('table thead th a').each(function() {
        var href = $(this).attr('href');
        if (href.indexOf('desc') !== -1) {
            $(this).attr({
                'title' : '???????????? ??????'
            });
        } else {
            $(this).attr({
                'title' : '???????????? ??????'
            });
        }

        if (qry['ordsc'] == 'asc') {
            qry['order'] = 'desc';
            qry['icon'] = '<i class="fas fa-caret-up"></i>';
        } else {
            qry['order'] = 'asc';
            qry['icon'] = '<i class="fas fa-caret-down"></i>';
        }

        if (href.indexOf('&ordtg='+qry['ordtg']+'&ordsc='+qry['order']) != -1) {
            $(this).html($(this).text() + qry['icon']);
        }
    });
});

///
// UI: datepicker
///
$(function() {
    $('input[datepicker]').datepicker();
    $.datepicker.setDefaults({
        dateFormat: 'yy-mm-dd',
        prevText: '?????? ???',
        nextText: '?????? ???',
        monthNames: ['1???', '2???', '3???', '4???', '5???', '6???', '7???', '8???', '9???', '10???', '11???', '12???'],
        monthNamesShort: ['1???', '2???', '3???', '4???', '5???', '6???', '7???', '8???', '9???', '10???', '11???', '12???'],
        dayNames: ['???', '???', '???', '???', '???', '???', '???'],
        dayNamesShort: ['???', '???', '???', '???', '???', '???', '???'],
        dayNamesMin: ['???', '???', '???', '???', '???', '???', '???'],
        showMonthAfterYear: true,
        yearSuffix: '???'
    });
});

$(function() {
    $nowdate = $('#list-sch input[name=nowdate]');
    $fdate = $('#list-sch input[name=fdate]');
    $tdate = $('#list-sch input[name=tdate]');

    $fdate.datepicker('option', 'maxDate', $nowdate.val());
    $tdate.datepicker('option', 'maxDate', $nowdate.val());
    $fdate.datepicker('option', 'onClose', function(selectedDate){
        $tdate.datepicker('option', 'minDate', selectedDate);
    });
    $tdate.datepicker('option', 'onClose', function(selectedDate) {
        $fdate.datepicker('option', 'maxDate', selectedDate);
    });
});

///
// main.tpl.php
///
$(function() {
    $('#dashboard .news-wrap a.view-feed-link').on({
        'click' : function(e) {
            e.preventDefault();
            var page = $('#dashboard .news-wrap input[name=page]').val();
            var idx = $(this).data('feed-idx');
            var href = $(this).data('feed-href');
            window.open(href);
            window.document.location.href = PH_MANAGE_DIR + "/main/dash?view_dash_feed=" + idx+'&page=' + page;
        }
    });
});

///
// siteinfo/theme.tpl.php
///
$(function() {
    $('#themeForm input[name=theme_slt]').on({
        'change' : function(e) {
            e.preventDefault();
            $('#themeForm').submit();
        }
    });
});

///
// siteinfo/sitemap.tpl.php
///
var sitemap_list = {
    'init' : function() {
        this.action();
    },
    'action' : function(functions) {
        var $wait_box = $('#sitemapMofidyForm .sitemap-wait').clone();
        var list_arr = new Array;
        list_arr[0] = {
            'axis' : 'y',
            'stop' : function() {
                $('#sitemapListForm input[name=type]').val('modify');
                list_refrs();
            }
        }
        var $list_ele = new Array;

        var get_sortable = function() {
            $list_ele[0] = $('#sitemapListForm .sortable');
            $list_ele[1] = $('#sitemapListForm .st-2d');
            $list_ele[2] = $('#sitemapListForm .st-3d');
            $list_ele[0].sortable(list_arr[0]).disableSelection();
            $list_ele[1].sortable(list_arr[0]).disableSelection();
            $list_ele[2].sortable(list_arr[0]).disableSelection();
        }

        var list_charlen = function(str) {
            if (escape(str).length < 3) {
                var min = 4 - escape(str).length;
                var output = '';
                for (var i = 0; i < min; i++) {
                    output += '0';
                }
            }
            output = output + str;
            return output;
        }

        var request_sbm = function() {
            $('#sitemapListForm').submit();
        }

        var list_reload = function() {
            $('#sitemapListForm').load(PH_MANAGE_DIR + '/siteinfo/sitemapList', function(){
                get_sortable();
                $('.sitemap').removeClass('with-ajax-cover');
            });
        }
        list_reload();

        var list_refrs = function() {
            var eqidx = new Array();
            var eqval = new Array();

            $('.sitemap').addClass('with-ajax-cover');
            $('#sitemapListForm').append('<div class="ajax-cover"></div>');

            get_sortable();

            $list_ele[0].find('input[name="caidx[]"]').each(function() {
                var $this = $(this);
                var depth = $this.data('depth');

                if ($this.data('depth') === 1) {
                    eqidx[0] = parseInt($this.index('input[name="caidx[]"][data-depth=1]')) + 1;
                    eqval[0] = list_charlen(eqidx[0]);
                    if (eqidx[0]!=0) {
                        $this.val(eqval[0]);
                    }
                }
                if ($this.data('depth') === 2) {
                    eqidx[1] = parseInt($this.parents('li').index()) + 1;
                    eqval[1] = eqval[0] + list_charlen(eqidx[1]);
                    if (eqidx[1]!=0) {
                        $this.val(eqval[1]);
                    }
                }
                if ($this.data('depth') === 3) {
                    eqidx[2] = parseInt($this.parents('li').index()) + 1;
                    eqval[2] = eqval[1] + list_charlen(eqidx[2]);
                    if (eqidx[2] !== 0) {
                        $this.val(eqval[2]);
                    }
                }
            });

            $('input[name="idx[]"]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('sitemap_new_added_ele');

                } else {
                    $(this).removeClass('sitemap_new_added_ele');
                }
            })

            $('#sitemapListForm input[name=new_caidx]').val($('.sitemap_new_added_ele').eq(0).next('input[name="caidx[]"]').val());
            request_sbm();
            $('#sitemapListForm input[name=type]').val('add');
        }

        var secc_modify = function() {
            alert('??????????????? ?????? ???????????????.');
            list_reload();
        }

        if (functions) {
            eval(functions+'()');
            return false;
        }

        $(document).on('click', '#sitemapListForm .add-1d', function(e) {
            e.preventDefault();
            var html = '<div class="st-1d"><h4><a href="#" class="modify-btn"><input type="hidden" name="idx[]" value="" /><input type="hidden" name="caidx[]" value="" data-depth="1" /><input type="hidden" name="org_caidx[]" value="" />????????? 1??? ????????????</a><i class="fa fa-trash-alt st-del del-1d"></i></h4><div class="in"><ul class="st-2d"></ul><span class="st-no-cat">?????? ????????? 2??? ??????????????? ????????????.</span></div><a href="#" class="st-add add-2d"><i class="fa fa-plus"></i> 2??? ???????????? ??????</a></div>';
            $(html).hide().appendTo($('.sortable'));
            list_refrs();
        });

        $(document).on('click', '#sitemapListForm .add-2d', function(e) {
            e.preventDefault();
            var $this = $(this);
            var html = '<li><p><a href="#" class="modify-btn"><input type="hidden" name="idx[]" value="" /><input type="hidden" name="caidx[]" value="" data-depth="2" /><input type="hidden" name="org_caidx[]" value="" />????????? 2??? ????????????</a><i class="fa fa-plus add-3d"></i><i class="fa fa-trash-alt st-del del-2d"></i></p><ul class="st-3d"></ul></li>';
            $(html).hide().appendTo($this.parents('.st-1d').find('.st-2d')).fadeIn(200, function(){
                $this.parents('.st-1d').find('.st-2d').sortable(list_arr[0]);
                list_refrs();
            });
            $(this).parents('.st-1d').find('.st-no-cat').remove();
        });

        $(document).on('click', '#sitemapListForm .add-3d', function(e) {
            e.preventDefault();
            var $this = $(this);
            var html = '<li><p><a href="#" class="modify-btn"><input type="hidden" name="idx[]" value="" /><input type="hidden" name="caidx[]" value="" data-depth="3" /><input type="hidden" name="org_caidx[]" value="" />????????? 3??? ????????????</a><i class="fa fa-trash-alt st-del del-3d"></i></p></li>';
            $(html).hide().appendTo($this.parents('li').find('.st-3d')).fadeIn(200, function() {
                $this.parents('li').find('.st-3d').sortable(list_arr[0]);
                list_refrs();
            });
        });

        $(document).on('click', '#sitemapListForm .del-1d', function(e) {
            e.preventDefault();
            if (!confirm('???????????? ?????? ????????? ??? ????????????.\n\n????????? ?????? ???????????????????')) {
                return false;
            }
            var $this = $(this);
            $this.parents('.st-1d').remove();
            $('#sitemapListForm input[name=type]').val('modify');
            $('#sitemapMofidyForm').empty().append($wait_box);
            list_refrs();
        });

        $(document).on('click', '#sitemapListForm .del-2d', function(e) {
            e.preventDefault();
            if (!confirm('???????????? ?????? ????????? ??? ????????????.\n\n????????? ?????? ???????????????????')) {
                return false;
            }
            var $this = $(this);
            $this.parent().parent('li').remove();
            $('#sitemapListForm input[name=type]').val('modify');
            $('#sitemapMofidyForm').empty().append($wait_box);
            list_refrs();
        });

        $(document).on('click', '#sitemapListForm .del-3d', function(e) {
            e.preventDefault();
            if (!confirm('???????????? ?????? ????????? ??? ????????????.\n\n????????? ?????? ???????????????????')) {
                return false;
            }
            var $this = $(this);
            $this.parent().parent('li').remove();
            $('#sitemapListForm input[name=type]').val('modify');
            $('#sitemapMofidyForm').empty().append($wait_box);
            list_refrs();
        });

        $(document).on('click', '#sitemapListForm a.modify-btn', function(e) {
            e.preventDefault();
            var idx = $(this).find('input[name="idx[]"]').val();
            $('#sitemapMofidyForm').hide().load(PH_MANAGE_DIR + '/siteinfo/sitemapModify?idx=' + idx).fadeIn(100);
        });
    }
}
$(function() {
    sitemap_list.init();
});

///
// mailler/send.tpl.php
///
$(function(){
    $('#sendmailForm input[name=type]').on({
        'click' : function(e){
            var type = $(this).val();
            $('#sendmailForm table tr.hd-tr[data-type='+type+']').show().siblings('.hd-tr').hide();
        }
    });
});

///
// sms/tomember.tpl.php
///
$(function(){

    //?????? byte??? ??????
    var get_sms_memobyte = function(val) {
        var bytes = val.replace(/[\0-\x7f]|([0-\u07ff]|(.))/g,"$&$1$2").length;
        bytes = parseInt(bytes);

        $btn_txt = $('#smsSendForm button[type=submit] > strong');

        if (bytes >= 80) {
            $btn_txt.text('LMS');
        } else {
            $btn_txt.text('SMS');
        }

        if ($('#smsSendForm input[name=image]').val() != '') {
            $btn_txt.text('MMS');
        }

        return bytes;
    }
    var get_sms_timer;
    var get_sms_printbyte = function() {
        if (get_sms_timer) {
            clearTimeout(get_sms_timer);
        }
        $('#smsSendForm .print_byte > strong > strong').text(get_sms_memobyte($('#smsSendForm #memo').val()));
        get_sms_timer = setTimeout(get_sms_printbyte, 100);
    }
    if ($('#smsSendForm .print_byte').length > 0) {
        get_sms_timer = setTimeout(get_sms_printbyte, 100);
    }

    //?????? ?????? ??????
    $('#smsSendForm input[name=type]').on({
        'click' : function(e){
            var type = $(this).val();
            $('#smsSendForm table tr.hd-tr[data-type='+type+']').show().siblings('.hd-tr').hide();
        }
    });

    //?????? ?????? ??????
    var get_sms_resv = function(type) {
        if (type == 'show') {
            $('#smsSendForm .resv-wrap *').attr('disabled', false);

        } else if (type == 'hide') {
            $('#smsSendForm .resv-wrap *').attr('disabled', true);
        }
    }
    $('#smsSendForm .resv-btn').on({
        'click' : function() {
            var chked = $(this).find(':checkbox').prop('checked');
            if (chked == true) {
                get_sms_resv('show');

            } else {
                get_sms_resv('hide');
            }
        }
    })
    get_sms_resv('hide');
})
