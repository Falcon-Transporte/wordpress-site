document.addEventListener("DOMContentLoaded", function () {

    const links = document.querySelectorAll("#indice a");

    const chapters = document.querySelectorAll(".capitulo");

    const conteudoGrid = document.querySelector(".conteudo-grid");



    function hideAllChapters() {

        chapters.forEach(chapter => chapter.classList.remove("ativo"));

    }



    links.forEach(link => {

        link.addEventListener("click", function (e) {

            e.preventDefault(); 

            const chapterId = this.getAttribute("data-chapter");

            const targetChapter = document.getElementById(chapterId);



            if (targetChapter) {

                hideAllChapters();

                targetChapter.classList.add("ativo");



                targetChapter.scrollIntoView({ behavior: 'smooth', block: 'start' });

            }

        });

    });



    if (chapters.length > 0) {

        chapters[0].classList.add("ativo");

    }

});