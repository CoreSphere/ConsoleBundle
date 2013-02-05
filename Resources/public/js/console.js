/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

window.CoreSphereConsole = (function (window) {
    "use strict";

    if (typeof window.jQuery === "undefined") {
        window.alert('jQuery has not been loaded');
        return;
    }

    var $ = window.jQuery,

        default_options = {
            'filters' : {
                'clear' : function () {
                    this.log.html('');

                    return '';
                }
            },

            'active_suggestion_class' : 'active'
        },

        helpers = {

            htmlEscape : function (input) {
                return input
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            },

            regexpEscape : (function () {
                var regexp = new RegExp(
                    '(\\' + [
                        '.', '*', '+', '?', '|',
                        '^', '$',
                        '(', ')', '[', ']', '{', '}', '\\'
                    ].join('|\\') + ')',
                    'g'
                );

                return function (input) {
                    return input.replace(regexp, '\\$1');
                };
            }()),

            focusInput : function (el) {
                var range, sel, textRange;
                el.focus();
                if (typeof window.getSelection !== "undefined"
                        && typeof window.document.createRange !== "undefined") {
                    range = window.document.createRange();
                    sel = window.getSelection();
                    range.selectNodeContents(el);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);
                } else if (typeof window.document.body.createTextRange !== "undefined") {
                    textRange = window.document.body.createTextRange();
                    textRange.moveToElementText(el);
                    textRange.collapse(false);
                    textRange.select();
                }
            }

        },

        keys = {
            'tab' : 9,
            'enter' : 13,
            'escape' : 27,
            'left' : 37,
            'up' : 38,
            'right' : 39,
            'down': 40
        },

        Console = function (base_element, options) {
            this.options = $.extend(default_options, options);

            this.base             = base_element;
            this.input            = base_element.find('.console_input');
            this.input_background = base_element.find('.console_input_background');
            this.log_container    = base_element.find('.console_log_container');
            this.log              = base_element.find('.console_log');
            this.suggestion_box   = base_element.find('.console_suggestions');
            this.active_suggestion = null;

            this.initHistory();
            this.bindEvents();
            this.unlock();
            this.focus();
            this.welcome();
        };



    Console.prototype.focus = function () {
        helpers.focusInput(this.input[0]);
    };

    Console.prototype.initHistory = function () {
        if (!window.localStorage.coresphere_console_history) {
            window.localStorage.coresphere_console_history = '[]';
        }

        this.history = JSON.parse(window.localStorage.coresphere_console_history).slice(-20);
        this.history_position = this.history.length;
    };

    Console.prototype.pushHistory = function (val) {
        if (val.length && this.history[this.history.length - 1] !== val) {
            this.history.push(val);
            window.localStorage.coresphere_console_history = JSON.stringify(this.history);
        }

        this.history_position = this.history.length;
    };

    Console.prototype.clearHistory = function () {
        window.localStorage.removeKey('coresphere_console_history');
    };

    Console.prototype.bindEvents = function () {
        var enable_suggestions = true,
            this_console = this;

        this.base

            .on('click.coresphere_console', '.console_command', function (e) {
                enable_suggestions = true;
                this_console.setValue($(this).text()).trigger('keyup');
                this_console.focus();
            })

            .on('mouseover.coresphere_console', '.console_suggestions li', function (e) {
                var $this = $(this);

                this_console.suggestion_box.find('.' + this_console.options.active_suggestion_class).removeClass(this_console.options.active_suggestion_class);
                $this.addClass(this_console.options.active_suggestion_class);
                this_console.setActiveSuggestion($this.text());

                this_console.focus();
            })

            .on('click.coresphere_console', '.console_suggestions li', function (e) {
                e.stopPropagation();
                var $this = $(this);
                this_console.setValue($this.text());
                this_console.clearSuggestions();
                this_console.focus();
            })

            .on('click.coresphere_console', '.console_log_input', function (e) {
                $(this).next('.console_log_output').stop().slideToggle(100);
            })

            .on('keydown.coresphere_console', '.console_input', function (e) {
                var val,
                    command,
                    filter,
                    current_suggestions,
                    active_suggestion,
                    next;

                if (this_console.isLocked()) {
                    return;
                }

                val = this_console.getValue();

                if (e.which === keys.tab) {

                    e.preventDefault();

                    if (this_console.active_suggestion) {
                        this_console.setValue(this_console.active_suggestion);
                    }

                    this_console.focus();

                } else if (e.which === keys.enter && !e.shiftKey) {

                    if (this_console.active_suggestion) {
                        if (val !== this_console.active_suggestion) {
                            this_console.setValue(val = this_console.active_suggestion);
                            this_console.clearSuggestions();
                            this_console.focus();

                            e.preventDefault();
                            return;
                        }
                    }

                    command = val;

                    this_console.setValue('');

                    this_console.pushHistory(val);

                    if (command.substr(0, 1) === '.') {
                        filter = command.substr(1);

                        if (this_console.options.filters[filter]) {
                            command = this_console.options.filters[filter].call(this_console);
                        }
                    }

                    if (command.length) {
                        this_console.lock();

                        this_console.log.find('li:not(.console_loading) .console_log_output').last().hide();

                        this_console.log.append('<li class="console_loading"><div class="console_log_input">' + helpers.htmlEscape(val) + '</div><div class="console_log_output">' + this_console.options.lang.loading + '</div></li>');

                        this_console.sendCommand(command);
                    } else {
                        this_console.focus();
                    }

                    e.preventDefault();

                } else if (e.which === keys.up || e.which === keys.down) {

                    current_suggestions = this_console.suggestion_box.find('li');
                    active_suggestion = current_suggestions.filter('.' + this_console.options.active_suggestion_class);

                    if (e.which === keys.up) {
                        if (current_suggestions.size()) {
                            next = active_suggestion.size() ? active_suggestion.removeClass(this_console.options.active_suggestion_class).prev() : current_suggestions.last();
                            next = next.size() ? next : current_suggestions.last();
                            this_console.setActiveSuggestion(next.addClass(this_console.options.active_suggestion_class).text());
                        } else {
                            this_console.history_position -= 1;
                            if (this_console.history_position < 0) {
                                this_console.history_position = 0;
                            } else {
                                this_console.setValue(this_console.history[this_console.history_position]);
                            }
                        }
                    } else {
                        // DOWN

                        if (current_suggestions.size()) {
                            next = active_suggestion.size() ? active_suggestion.removeClass(this_console.options.active_suggestion_class).next() : current_suggestions.first();
                            next = next.size() ? next : current_suggestions.first();

                            this_console.setActiveSuggestion(next.addClass(this_console.options.active_suggestion_class).text());
                        } else {
                            this_console.history_position += 1;
                            if (this_console.history_position >= this_console.history.length) {
                                this_console.history_position = this_console.history.length;
                                this_console.setValue('');
                            } else {
                                this_console.setValue(this_console.history[this_console.history_position]);
                            }
                        }

                    }

                    this_console.focus();
                    enable_suggestions = false;

                    e.preventDefault();

                } else if (e.which === keys.escape) {

                    if (this_console.suggestion_box.find('li').size()) {
                        this_console.suggestion_box.text('');
                    } else {
                        this_console.setValue('');
                    }
                    enable_suggestions = false;
                    this_console.focus();
                }

                if ((e.which < keys.left || e.which > keys.down) && e.which !== keys.escape) {
                    enable_suggestions = true;
                }
            })

            .on('keyup.coresphere_console', '.console_input', function (e) {

                if (enable_suggestions) {
                    var val = this_console.getValue(),
                        best_suggestions = [],
                        other_suggestions = [],
                        suggestions,
                        any = 0,
                        htmlcode,
                        index,
                        j;


                    if (val.length) {
                        for (index = 0, j = this_console.options.commands.length; index < j; index += 1) {
                            if (new RegExp('^' + helpers.regexpEscape(val)).test(this_console.options.commands[index])) {
                                best_suggestions.push(this_console.options.commands[index]);
                                any += 1;
                            } else if (new RegExp(helpers.regexpEscape(val)).test(this_console.options.commands[index])) {
                                other_suggestions.push(this_console.options.commands[index]);
                                any += 1;
                            }
                            if (this_console.options.commands[index] === val) {
                                any -= 1;
                            }
                        }

                        suggestions = best_suggestions.concat(other_suggestions);
                    }

                    if (any) {
                        if (!this_console.active_suggestion || suggestions.indexOf(this_console.active_suggestion) < 0) {
                            this_console.setActiveSuggestion(suggestions[0]);
                        } else {
                            this_console.setActiveSuggestion(this_console.active_suggestion);
                        }


                        enable_suggestions = false;

                        htmlcode  = '<h2 class="console_suggestion_head">' + this_console.options.lang.suggestion_head + '</h2>';
                        htmlcode += '<ul>';
                        for (index = 0, j = suggestions.length; index < j; index += 1) {
                            htmlcode += suggestions[index] === this_console.active_suggestion ? '<li class="active">' : '<li>';
                            htmlcode += suggestions[index].replace(val, '<strong class="match">' + val + '</strong>') + '</li>';
                        }
                        htmlcode += '</ul>';
                        this_console.suggestion_box.html(htmlcode);
                    } else {
                        this_console.setActiveSuggestion(null);

                        this_console.suggestion_box.text('');
                    }
                }

            })

            .on('focus.coresphere_console', '.console_input', function (e) {
                this_console.suggestion_box.show();
            });

        $(window.document)

            .on('mousedown.coresphere_console', function (e) {
                var $target = $(e.target);
                this_console.focus();
                if ($target.is('.console_input')
                        || $target.is('.console_suggestions')
                        || $target.is('.console_suggestions li')
                        ) {

                    return;
                }
                this_console.suggestion_box.hide();
            })

            .on('focus.coresphere_console', function (e) {
                this_console.focus();
            });
    };

    Console.prototype.unbindEvents = function () {
        this.base.off('.coresphere_console');
        $(window.document).off('.coresphere_console');
    };


    Console.prototype.lock = function () {
        this.locked = true;
    };

    Console.prototype.unlock = function () {
        this.locked = false;
    };

    Console.prototype.isLocked = function () {
        return this.locked === true;
    };

    Console.prototype.getValue = function () {
        return this.input.text();
    };

    Console.prototype.setValue = function (val) {
        this.setActiveSuggestion(null);
        return this.input.text(val);
    };

    Console.prototype.getActiveSuggestion = function () {
        return this.active_suggestion;
    };

    Console.prototype.setActiveSuggestion = function(suggestion) {
        this.active_suggestion = suggestion;
        this.input_background.text(this.active_suggestion || '');

        if(suggestion) {
            this.input.attr('data-before', suggestion.substr(0, suggestion.indexOf(this.input.text())));
        } else {
            this.input.attr('data-before', '');
        }
    };

    Console.prototype.clearSuggestions = function () {
        this.setActiveSuggestion(null);
        return this.suggestion_box.text('');
    };

    Console.prototype.welcome = function () {
        this.log.append('<li>' + this.options.lang.welcome_message + '</li>');
    };

    Console.prototype.sendCommand = function (command) {

        var this_console = this;

        return $.ajax({
            url: this.options.post_path,
            type: "POST",
            data: ({"command" : command}),
            dataType: "json"
        })

            .done(function (json) {
                var answer, htmlCode, cmd;

                for (var i = 0, len = json.length; i < len; i++) {
                    cmd = json[i];
                    answer = cmd.output.replace(/^\s+|\s+$/g, "");
                    htmlCode = '<li><div class="console_log_input">'
                                + helpers.htmlEscape(cmd.command)

                                + (cmd.environment !== this_console.options.environment ?
                                    '<span class="console_env_info">' + this_console.options.lang.environment + ': <strong>'
                                    + cmd.environment
                                    + '</strong></span>'
                                    :
                                    ''
                               )

                                + '</div><div class="console_log_output">'
                                + (answer.length ? answer : this_console.options.lang.empty_response)
                                + '</div></li>';

                    this_console.log.append(htmlCode);
                }
            })

            .fail(function (xhr, msg, error) {
                this_console.log.append('<li class="console_error"><div class="console_log_input">' + helpers.htmlEscape(command) + '</div><div class="console_log_output">[' + msg + '] ' + error + '</div></li>');
            })

            .then(function () {
                this_console.log.find('.console_loading').remove();
                this_console.unlock();
                this_console.log_container.scrollTop(this_console.log.outerHeight());
                this_console.focus();
            });
    };

    return Console;

}(window));
