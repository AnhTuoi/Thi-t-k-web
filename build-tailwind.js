const fs = require('fs');
const path = require('path');
const postcss = require('postcss');
const tailwindcss = require('@tailwindcss/postcss');
const autoprefixer = require('autoprefixer');

// Load the config in build-time
const configPath = path.join(__dirname, 'tailwind.config.js');
const tailwindConfig = require(configPath);

const inputPath = path.join(__dirname, 'Fontend', 'css', 'tailwind.css');
const outPath = path.join(__dirname, 'Fontend', 'css', 'dist.css');

async function build() {
  try {
    const css = fs.readFileSync(inputPath, 'utf8');
    const result = await postcss([
      tailwindcss(tailwindConfig), 
      autoprefixer()
    ]).process(css, {
      from: inputPath,
      to: outPath,
    });

    fs.writeFileSync(outPath, result.css);
    if (result.map) fs.writeFileSync(outPath + '.map', result.map.toString());
    console.log('✓ Tailwind CSS built to', outPath);
  } catch (err) {
    console.error('Build failed:', err.message || err);
    process.exit(1);
  }
}

build();
