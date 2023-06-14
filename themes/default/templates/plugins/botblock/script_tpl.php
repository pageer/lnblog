function addIt() {
    var res = <?php echo $RAND1?> <?php echo $OPERATION?> <?php echo $RAND2?>;
    if (document.getElementById('link')) {
        document.getElementById('link').value = res;
        document.getElementById('ic_block').style.display = 'none';
    }
}
window.addEventListener('load', addIt, false);
