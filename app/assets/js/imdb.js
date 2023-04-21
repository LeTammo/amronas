import axios from "axios";
const RAPID_API_KEY = process.env.RAPID_API_KEY;
const titleType = 'movie,tvSeries,short,tvEpisode,tvMiniSeries,tvMovie,tvSpecial,tvShort,videoGame';

$(document).ready(function () {
    let searchInput = $('#imdb-search');
    let searchResults = $('#search_results');
    let focusoutTimeoutId = 0;
    let keyupTimoutId = 0;

    searchInput.keyup(function () {
        clearTimeout(keyupTimoutId);
        keyupTimoutId = setTimeout(function() {
            let query = searchInput.val();
            if (query.length > 2) {
                getMovie(query)
            }
        }, 500);
    })

    searchInput.focusout(function () {
        focusoutTimeoutId = setTimeout(function() {
            searchResults.addClass('d-none');
        }, 500);
    });

    searchInput.focusin(function () {
        clearTimeout(focusoutTimeoutId);
        if (searchInput.val().length > 2) {
            searchResults.removeClass('d-none');
        }
    });

    $('.removeMovie').each(function () {
        $(this).click(function () {
            removeMovie($(this));
        })
    })
});

function getMovie(query) {
    fetchImdb({q: query}, 'auto-complete', function (data) {
        let searchResults = $('#search_results');
        searchResults.empty().removeClass('d-none');
        searchResults.append($('<div>').attr('id', 'loading').text("Loading...").addClass('p-4 text-center'));

        data['d'].forEach(function (movie, index) {
            const params = {
                id: movie['id'],
                name: movie['l'],
                year: movie['y'],
                posterUrl: movie['i']['imageUrl'],
            }
            const urlSearchParams = new URLSearchParams(params);

            let row = $('<a>')
                .addClass('search-result')
                .click(function () {
                    getGenre(movie['id']).then(genresArray => {
                        params['genres'] = genresArray.join(',');
                        let url = searchResults.data('url');
                        axios.post(url, params).then(promise => $("body").html(promise.data))
                    })
                })

            let img = $('<img alt="" src="">')
                .attr('src', movie['i']['imageUrl'])
                .attr('alt', movie['l'])
                .addClass('preview mx-2')

            row.append($('<div>')
                .addClass('search-result-img')
                .append(img));

            row.append($('<div>')
                .text(movie['y'])
                .addClass('fw-bold px-2'));

            row.append($('<div>')
                .text(movie['l'])
                .addClass('fw-bold px-2'));

            row.css('cursor', 'pointer');

            searchResults.append(row);
            $('#loading').remove();
        })
    }, '/responses/response.json');
}

function getGenre(id) {
    return fetchImdb({tconst: id}, 'title/get-genres', data => data, '/responses/response3.json');
}

function fetchImdb(params, path, funct, file) {
    //return fetch(file).then(response => response.json()).then(jsonResponse => funct(jsonResponse));

    let options = {
        method: 'GET',
        url: 'https://imdb8.p.rapidapi.com/' + path,
        params: params,
        headers: {
            'x-rapidapi-host': 'imdb8.p.rapidapi.com',
            'x-rapidapi-key': RAPID_API_KEY
        }
    };

    return axios.request(options)
        .then(response => funct(response.data))
        .catch(error => console.error(error));
}

function removeMovie(element) {
    let options = {
        method: 'POST',
        url: element.data('url'),
        params: {
            'id': element.data('id'),
        },
        headers: {
            'x-rapidapi-host': 'imdb8.p.rapidapi.com',
            'x-rapidapi-key': RAPID_API_KEY
        }
    };

    axios.request(options).then(promise => window.location.reload())
}