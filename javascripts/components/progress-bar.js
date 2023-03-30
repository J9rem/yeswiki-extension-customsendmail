/*
 * This file is part of the YesWiki Extension customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export default {
    props: ['donefor','bazarsendmail'],
    computed: {
        currentValue(){
            if (this.donefor === null){
                console.log({doneFor:null})
                return 0
            }
            const doneForLen = this.donefor.length
            const contactsLen = this.bazarsendmail.getContacts().length
            return (contactsLen == 0) ? 100 : Math.min(100,Math.floor(doneForLen/contactsLen*100))
        }
    },
    template: `
        <div class="progressbar progress">
            <div class="progress-bar" role="progressbar"
                :style="{width: currentValue+'%'}"
                :aria-valuenow="currentValue" 
                aria-valuemin="0" 
                aria-valuemax="100">
            </div>
        </div>
    `
}