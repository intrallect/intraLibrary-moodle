## Installation

First clone the repo `git clone git@github.com:intrallect/intraLibrary-moodle.git`, then you a few options.
In order to use the first two options you need to have [Phing](http://www.phing.info/) installed (tested with 2.4.14):

- Use `phing build_archive` to generate a single zip file (`build/intralibrary-moodle-plugins.zip`) that can be unzipped into your Moodle directory *(Recommended)*
- Use `phing build_archives` to generate individual zip files for each plugin (will land in `build/`), which can be separately unzipped into your Moodle directory
- If you don't have Phing, copy all directories from `src` to your Moodle directory

## Documentation

Available [here](http://knowledge.intrallect.com/manuals/moodle/MoodlePluginDocumentation.pdf) (not necessarily up to date).
