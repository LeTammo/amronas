
// any CSS you import will output into a single css file (app.css in this case)
import './styles/root.css';
import './styles/global.scss';
import './styles/app.css';
import './styles/wordle.css';
import './styles/bingo.css';

// start the Stimulus application
import './bootstrap';
import './js/imdb';
import './js/base';
import './js/tableSort';
import './js/tableFilter';
import './js/wordle';
import './js/password';

const $ = require('jquery');
require('bootstrap');
require('bootstrap/js/dist/popover');
require('moment');

// start the Stimulus application
import './bootstrap';

global.$ = global.jQuery = $;

$(document).ready(function() {
    $('[data-toggle="popover"]').popover();
});