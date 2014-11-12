## Installation

- `git clone git@github.com:intrallect/intraLibrary-moodle.git`.
- `git submodule init && git submodule update`

Then you a few options. In order to use the first two you need to have [Phing](http://www.phing.info/) installed (tested with 2.4.14):

- Use `phing build_archive` to generate a single zip file (`build/intralibrary-moodle-plugins.zip`) that can be unzipped into your Moodle directory **(Recommended)**
- Use `phing build_archives` to generate individual zip files for each plugin (will land in `build/`), which can be separately unzipped into the respective plugin subdirectories (eg. `repository/`) in your Moodle directory
- If you don't have Phing, copy all directories from `src` to your Moodle directory

## Requirements

This set of plugins require APIs available in intraLibrary 3.7 and above and should be used with Moodle 2.7.*

## Documentation

Available [here](http://knowledge.intrallect.com/manuals/moodle/MoodlePluginDocumentation.pdf)

## Licence

The intraLibrary-moodle plugin package is licensed under [*GPL v3*](LICENCE-GPLv3.txt)
