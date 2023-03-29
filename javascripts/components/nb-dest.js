/*
 * This file is part of the YesWiki Extension customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export default {
    props: ['availableentries','bazarsendmail'],
    methods: {
        formatText(text,nb){
            return text.replace('{nb}',nb)
        }
    },
    computed: {
        nb(){
            return this.availableentries.filter((e)=>this.bazarsendmail.isChecked(e)).length
        },
        pluraltext() {
            return this.bazarsendmail.fromSlot('pluralnbdesttext')
        },
        singulartext() {
            return this.bazarsendmail.fromSlot('singularnbdesttext')
        },
        text(){
            return this.formatText(this.nb > 1 ? this.pluraltext : this.singulartext, this.nb)
        }
    },
    template: `
        <span v-html="text"></span>
    `
}