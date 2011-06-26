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

    if (typeof window.jQuery === "undefied") {
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
                    .split("&").join("&amp;")
                    .split("<").join("&lt;")
                    .split(">").join("&gt;");
            },

            regexpEscape : (function () {
                var regexp = new RegExp(
                    '(\\' + [
                        '.', '*', '+', '?', '|',
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
            this.log_container    = base_element.find('.console_log_container');
            this.log              = base_element.find('.console_log');
            this.suggestion_box   = base_element.find('.console_suggestions');
            this.active_suggestion = null;
            this.first_suggestion  = null;

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
        this.history.push(val);
        this.history_position = this.history.length;

        window.localStorage.coresphere_console_history = JSON.stringify(this.history);
    };

    Console.prototype.clearHistory = function () {
        window.localStorage.removeKey('coresphere_console_history');
    };

    Console.prototype.bindEvents = function () {
        var enable_suggestions = true,
            this_console = this;

        this.base

            .delegate('.console_command', 'click.coresphere_console', function () {
                enable_suggestions = true;
                this_console.setValue($(this).text()).trigger('keyup');
                this_console.focus();
            })

            .delegate('.console_suggestions li', 'mouseover.coresphere_console', function () {
                var $this = $(this);

                this_console.suggestion_box.find('.' + this.options.active_suggestion_class).removeClass(this.options.active_suggestion_class);
                $this.addClass(this.options.active_suggestion_class);
                this_console.active_suggestion = $this.text();

                this_console.focus();
            })

            .delegate('.console_suggestions li', 'click.coresphere_console', function (event) {
                event.stopPropagation();
                var $this = $(this);
                this_console.setValue($this.text());
                this_console.clearSuggestions();
                this_console.focus();
            })

            .delegate('.console_log_input', 'click.coresphere_console', function () {
                $(this).next('.console_log_output').stop().slideToggle(100);
            })

            .delegate('.console_input', 'keydown.coresphere_console', function (event) {
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

                if (event.which === keys.tab) {

                    event.preventDefault();

                    if (this_console.active_suggestion) {
                        this_console.setValue(this_console.active_suggestion);
                        this_console.focus();
                    } else if (this_console.first_suggestion) {
                        this_console.setValue(this_console.first_suggestion);
                        this_console.focus();
                    }

                } else if (event.which === keys.enter && !event.shiftKey) {

                    if (this_console.active_suggestion) {
                        if (val !== this_console.active_suggestion) {
                            this_console.setValue(val = this_console.active_suggestion);
                            this_console.clearSuggestions();
                            this_console.focus();

                            event.preventDefault();
                            return;
                        }
                    }

                    command = val;

                    this_console.setValue('');

                    if (val.length && this_console.history[this_console.history.length - 1] !== val) {
                        this_console.pushHistory(val);
                    }

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

                    event.preventDefault();

                } else if (event.which === keys.up || event.which === keys.down) {

                    current_suggestions = this_console.suggestion_box.find('li');
                    active_suggestion = current_suggestions.filter('.' + this.options.active_suggestion_class);

                    if (event.which === keys.up) {
                        if (current_suggestions.size()) {
                            next = active_suggestion.size() ? active_suggestion.removeClass(this.options.active_suggestion_class).prev() : current_suggestions.last();
                            next = next.size() ? next : current_suggestions.last();
                            this_console.active_suggestion = next.addClass(this.options.active_suggestion_class).text();
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
                            next = active_suggestion.size() ? active_suggestion.removeClass(this.options.active_suggestion_class).next() : current_suggestions.first();
                            next = next.size() ? next : current_suggestions.first();
                            this_console.active_suggestion = next.addClass(this.options.active_suggestion_class).text();
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

                    event.preventDefault();

                } else if (event.which === keys.escape) {

                    if (this_console.suggestion_box.find('li').size()) {
                        this_console.suggestion_box.text('');
                    } else {
                        this_console.setValue('');
                    }
                    enable_suggestions = false;
                    this_console.focus();
                }

                if ((event.which < keys.left || event.which > keys.down) && event.which !== keys.escape) {
                    enable_suggestions = true;
                }
            })

            .delegate('.console_input', 'keyup.coresphere_console', function () {

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
                        this_console.first_suggestion = best_suggestions[0];

                        if (!this_console.active_suggestion) {
                            this_console.active_suggestion = this_console.first_suggestion;
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
                        this_console.first_suggestion = null;
                        this_console.active_suggestion = null;

                        this_console.suggestion_box.text('');
                    }
                }

            })

            .delegate('.console_input', 'focus.coresphere_console', function () {
                this_console.suggestion_box.show();
            });

        $(window)

            .bind('mousedown.coresphere_console', function () {
                var $target = $(event.target);
                if ($target.is('.console_input')
                        || $target.is('.console_suggestions')
                        || $target.is('.console_suggestions li')
                        ) {
                    return;
                }
                this_console.suggestion_box.hide();
            })

            .bind('focus.coresphere_console', function () {
                this_console.focus();
            });
    };

    Console.prototype.unbindEvents = function () {
        this.base.undelegate('.coresphere_console');
        window.unbind('.coresphere_console');
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
        return this.input.text(val);
    };

    Console.prototype.getActiveSuggestion = function () {
        return this.active_suggestion;
    };

    Console.prototype.clearSuggestions = function () {
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

            .success(function (json) {
                var answer = json.output.replace(/^\s+|\s+$/g, ""),
                    htmlcode = '<li><div class="console_log_input">'
                                + helpers.htmlEscape(command)

                                + (json.environment !== this_console.options.environment ?
                                    '<span class="console_env_info">' + this_console.options.lang.environment + ': <strong>'
                                    + json.environment
                                    + '</strong></span>'
                                    :
                                    ''
                               )

                                + '</div><div class="console_log_output">'
                                + (answer.length ? answer : this_console.options.lang.empty_response)
                                + '</div></li>';


                this_console.log.append(htmlcode);
            })

            .error(function (xhr, msg, error) {
                this_console.log.append('<li class="console_error"><div class="console_log_input">' + helpers.htmlEscape(command) + '</div><div class="console_log_output">[' + msg + '] ' + error + '</div></li>');
            })

            .complete(function () {
                this_console.log.find('.console_loading').remove();
                this_console.unlock();
                this_console.log_container.scrollTop(this_console.log.outerHeight());
                this_console.focus();
            });
    };

    return Console;

}(window));