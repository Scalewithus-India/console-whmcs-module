const postcss = require('postcss');
const postcssImport = require('postcss-import');
const postcssNested = require('postcss-nested');
const tailwindcss = require('tailwindcss');
const autoprefixer = require('autoprefixer');
const cssnano =  require('cssnano');
const fs = require('fs');
const chokidar = require('chokidar');

const inputFile = './assets/css/style.css';
const outputFile = './assets/css/style.min.css';
const templatesFolder = './templates'; // Update with your templates folder path

const plugins = [
  postcssImport,
  postcssNested,
  tailwindcss,
  autoprefixer,
  cssnano
];

const processCSS = async () => {
  try {
    const css = fs.readFileSync(inputFile, 'utf8');

    const result = await postcss(plugins).process(css, {
      from: inputFile,
      to: outputFile,
      map: { inline: false },
    });

    fs.writeFileSync(outputFile, result.css);

    console.log('CSS build completed successfully.');
  } catch (error) {
    console.error('Error building CSS:', error);
  }
};

// Function to watch for CSS file changes
const watchCSS = () => {
  console.log('Watching for CSS file changes...');
  chokidar.watch(inputFile).on('change', () => {
    console.log('CSS file changed. Rebuilding...');
    processCSS();
  });
};

// Function to watch for template file changes
const watchTemplates = () => {
  console.log('Watching for template file changes...');
  chokidar.watch(templatesFolder).on('change', () => {
    console.log('Template file changed. Rebuilding CSS...');
    processCSS();
  });
};

// Start the initial CSS build
processCSS();

// Start watching for changes in watch mode
if (process.argv.includes('--watch')) {
  watchCSS();
  watchTemplates(); // Add this line to watch templates folder
}
