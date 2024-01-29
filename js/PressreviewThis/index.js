import { render } from '@wordpress/element'

import { PressreviewThis } from './Components/PressreviewThis'
import './style.scss'

document.addEventListener('DOMContentLoaded', (event) => {
  render(<PressreviewThis />, document.getElementById('cu-pressreview-this'))
})
