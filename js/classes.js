function updateStars(val) {
  for(let i=1;i<=5;i++) {
    document.getElementById('star-'+i).style.color = i<=val ? '#c0a030' : 'var(--surface-3)';
  }
}
