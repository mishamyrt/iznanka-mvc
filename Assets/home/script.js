'use strict';

export default class HomeController {
    static init() {
        const hello = document.querySelector('.hello-message')
        hello.style.opacity = 0
        hello.style.transform = 'translateY(-5vh)'
        hello.style.transition = 'opacity .9s ease-out, transform .4s ease-out'
        setTimeout(() => {
            hello.style.opacity = 1
            hello.style.transform = ''
            setTimeout(() => {
                hello.style.transition = ''
                hello.style.opacity = ''
            }, 1000)
        }, 200)
    }
}