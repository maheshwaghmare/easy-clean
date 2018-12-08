/**
 * AJAX Request Queue
 *
 * - add()
 * - remove()
 * - run()
 * - stop()
 *
 * @since 1.0.0
 */
var EasyCleanAjaxQueue = (function() {

    var requests = [];

    return {

        /**
         * Add AJAX request
         *
         * @since 1.0.0
         */
        add:  function(opt) {
            requests.push(opt);
        },

        /**
         * Remove AJAX request
         *
         * @since 1.0.0
         */
        remove:  function(opt) {
            if( jQuery.inArray(opt, requests) > -1 )
                requests.splice($.inArray(opt, requests), 1);
        },

        /**
         * Run / Process AJAX request
         *
         * @since 1.0.0
         */
        run: function() {
            var self = this,
                oriSuc;

            if( requests.length ) {
                oriSuc = requests[0].complete;

                requests[0].complete = function() {
                     if( typeof(oriSuc) === 'function' ) oriSuc();
                     requests.shift();
                     self.run.apply(self, []);
                };

                jQuery.ajax(requests[0]);

            } else {

              self.tid = setTimeout(function() {
                 self.run.apply(self, []);
              }, 1000);
            }
        },

        /**
         * Stop AJAX request
         *
         * @since 1.0.0
         */
        stop:  function() {

            requests = [];
            clearTimeout(this.tid);
        }
    };

}());

(function($) {

    EasyClean = {

        /**
         * Init
         */
        init: function()
        {
            this._bind();
            this.toggleoptions();
        },

        /**
         * Binds events
         */
        _bind: function()
        {
            $( document ).on('click', '.easy-clean-delete', EasyClean.deleteAll );
            $( document ).on('click', '.post-type-all', EasyClean.toggleAll );
            // $( document ).on('click', '.post-type .toggle', EasyClean.toggleoptions );
            
            $( document ).on('click', '.easy-clean-delete-log', EasyClean.delete_log );
        },

        delete_log: function() {

            if( ! confirm( 'Are you sure to delete all log entries? Click OK to delete all log entries.') ) {
                return;
            }

            $(this).addClass('updating-message');
            var nonce = $( this ).data('nonce') || '';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action : 'easy_clean_delete_logs',
                    _nonce : nonce,
                },
            })
            .done(function() {
                console.log("success");
                setTimeout(function() {
                    location.reload();
                }, 3000 );
            })
            .fail(function() {
                console.log("error");
            })
            .always(function() {
                console.log("complete");
            });
            
        },

        deleteAll: function() {

            var checkedInputs = $( 'input[name="post_ids[]"]:checked:not(.post-type-all)' );
            var delete_count  = checkedInputs.length;
            var complete      = 0;
            var nonce = $( this ).data('nonce') || '';


            if( delete_count ) {

                if( confirm( "Note: Your selected posts are permanently deleted!\n\Do you want to delete selected posts? \nSelect OK to delete selected posts. Or CANCEL to skip the process.") ) {

                    // Show popup.
                    tb_show('Deleting..', '#TB_inline&inlineId=testing-content', '' );

                    $("#TB_ajaxContent").append( $('#testing-content').children() );

                    // Add log title.
                    $('<div class="log-details"><p>Deleted <span class="complete">1</span> of '+delete_count+' posts.</p></div>').appendTo('#TB_closeAjaxWindow');

                    $("#TB_overlay").unbind("click");
                    $("#TB_imageOff").unbind("click");
                    $("#TB_closeWindowButton").unbind("click");

                    // Activate ALl Plugins.
                    EasyCleanAjaxQueue.stop();
                    EasyCleanAjaxQueue.run();

                    checkedInputs.each(function(index, el) {
                        var post_id = $(this).val();
                        var post_type = $(this).data('post-type') || 'post';

                        console.log( 'post_id: ' + post_id );

                        // $(this).parent().append('<span class="spinner is-active"></span>');
                        // $(this).hide();

                        $( 'input[name="post_ids[]"]' ).addClass('disabled');
                        $( 'input[name="post_ids[]"]' ).parent().addClass('disabled');

                        EasyCleanAjaxQueue.add({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                'action' : 'easy_clean_delete_posts',
                                post_id  : post_id,
                                post_type  : post_type,
                                _nonce : nonce,
                            },
                            success: function( result ){

                                console.log( $("#TB_ajaxContent").length );
                                if( result.success ) {

                                    // $( 'input[value="'+result.data.post_id+'"]' ).parents('.no').slideUp();

                                    complete++;

                                    $('.log-details .complete').text( complete );
                                    $( result.data.markup ).prependTo('#TB_ajaxContent');

                                    console.log( delete_count + ' == ' + complete );
                                    if( delete_count == complete ) {
                                        console.log( 'complete all..' );

                                        $('#TB_ajaxWindowTitle').text('Complete!');

                                        $("#TB_overlay, #TB_imageOff, #TB_closeWindowButton").on("click", function() {
                                            tb_remove();
                                            console.log( 'yes: ' );
                                        });

                                        $( '<div class="notice notice-success clean-post-notice"><p>Successfull delete all the posts. Reloading the page in 5 seconds..</p></div>' ).prependTo('#TB_ajaxContent');

                                        setTimeout(function() {
                                            location.reload();
                                        }, 5000 );

                                        // var interval = 5;
                                        // var interval = setInterval(function() {
                                        //     counter++;
                                        //     $('.reload_seconds').text( interval );
                                        //     if( counter == 5 ) {
                                        //         clearInterval(interval);
                                        //     }
                                        // }, 1000);
                                        // setTimeout(function() {
                                        // }, 1000 );
                                    }


                                    // $('#TB_window').animate({
                                    //     scrollTop: $("#TB_ajaxContent .notice:last-child").offset().top
                                    // });
                                }
                            }
                        });
                    });

                }
            } else {
                alert( "You have not selected any post! Please select the posts to delete.");
            }

        },

        toggleoptions: function() {
            $('.toggle').toggle(function() {
                $( this ).parents('.post-type').find('.no-5 ~ div:not(.toggle)').slideDown();
                $( this ).find('i').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            }, function() {
                $( this ).parents('.post-type').find('.no-5 ~ div:not(.toggle)').slideUp();
                $( this ).find('i').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            });
            
        },

        /**
         * Copy to Clipboard
         */
        toggleAll: function( event )
        {
            // event.preventDefaults();

            var post_type = $(this).data('post-type') || '';
            console.log( 'post_type: ' + post_type );

            var checked = $( this ).is( ':checked' );
            console.log( 'checked: ' + checked );

            if( checked ) {
                console.log( 'yes' );
            } else {
                console.log( 'no' );
            }
            if( checked ) {
                $('.post-type-'+post_type+'-checkbox').attr( 'checked', 'checked' );
            } else {
                $('.post-type-'+post_type+'-checkbox').removeAttr( 'checked' );
            }
        }
    };

    /**
     * Initialization
     */
    $(function() {
        EasyClean.init();
    });

})(jQuery);