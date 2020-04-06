/* eslint-env browser */

const FNAME_REGEXP = /^([\d\w]+)(:?:([\d\w]+))?(:?\[([0-9]+)\])?$/gi

export class zyxObject {
  constructor (uuid = null) {
    if (uuid === null) {
      uuid = ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16))
    }

    this.data = {
      cn: { value: null, type: 'string', order: 2 },
      description: { value: null, type: 'text' },
      reference: { value: null, type: 'string' },
      uuid: { value: uuid, type: 'uuid', readonly: true, order: 0 },
      type: { value: null, type: 'enum', order: 2 },
      xyz: { value: null, type: 'number' },
      objectclass: { value: 'object', type: 'definition', readonly: true, order: 1 }
    }
  }

  toSortedArray () {
    const arData = []
    for (let k in this.data) {
      arData.push([k, this.data[k]])
    }
    arData.sort((a, b) => {
      let oa = a[1].order
      let ob = b[1].order
      if (oa === undefined && ob === undefined) { return 0 }
      if (oa === undefined && ob !== undefined) { return 1 }
      if (oa !== undefined && ob === undefined) { return -1 }

      oa = parseInt(oa)
      ob = parseInt(ob)
      if (isNaN(oa) && isNaN(ob)) { return 0 }
      if (isNaN(oa) && !isNaN(ob)) { return 1 }
      if (!isNaN(oa) && isNaN(ob)) { return -1 }

      return oa - ob
    })
    return arData
  }

  fromFieldset (root = null) {
    if (!root) {
      root = document.getElementById(this.get('uuid'))
    }
    if (!root) { return false }

    for (let node = root.firstElementChild; node; node = node.nextElementSibling) {
      if (node.children.length > 0) { this.fromFieldset(node) }
      if (node.getAttribute('disabled')) { continue }
      let name = node.getAttribute('name')
      if (name === null) { continue }

      let matches = (new RegExp(FNAME_REGEXP)).exec(name)
      if (matches) {
        let idx = parseInt(matches[5])
        let opt = matches[3]
        name = matches[1]
        console.log(name, idx, opt, matches)
        if (isNaN(idx)) { continue }
        if (!this.has(name)) { continue }

        if (opt !== undefined) {
          this.setParam(name, idx, opt, node.value)
        } else {
          if (this.set(name, idx, node.value)) {
            window.requestAnimationFrame(() => {
              node.classList.remove('error')
              node.classList.add('success')
            })
          } else {
            this.reset(name, idx)
            window.requestAnimationFrame(() => {
              node.classList.add('error')
              node.classList.remove('success')
            })
          }
        }
      }
    }
  }

  toFieldset (language = null) {
    let fs = document.getElementById(this.get('uuid'))
    if (!fs) {
      fs = document.createElement('FIELDSET')
      fs.setAttribute('id', this.get('uuid'))
    }

    let fn
    if (window.zyxFieldsName) {
      for (let l of navigator.languages) {
        if (window.zyxFieldsName[l]) {
          language = l
          fn = window.zyxFieldsName[language]
          break
        }
      }
      if (!fn) {
        language = Object.keys(window.zyxFieldsName)[0]
        fn = window.zyxFieldsName[language]
      }
    }

    if (!window.zyxTabIndex) {
      window.zyxTabIndex = 1
    }

    let kCount = {}
    for (let v of this.toSortedArray()) {
      let label = v[0]
      let key = v[0]
      let data = v[1]
      if (fn && fn[key]) {
        label = fn[key]
      }

      let domInput
      let languageSelect = false
      switch (data.type) {
        case 'text':
          languageSelect = true
          domInput = document.createElement('TEXTAREA')
          domInput.value = data.value
          break
        case 'string':
          languageSelect = true
          /* fall through */
        case 'uuid':
        case 'definition':
        case 'number':
          domInput = document.createElement('INPUT')
          domInput.setAttribute('type', 'text')
          domInput.classList.add(data.type)
          domInput.value = data.value
          break
        case 'enum':
          domInput = document.createElement('SELECT')
          for (let c in data.choices) {
            let choice = data.choices[c]
            let label = choice.label[language]
              ? choice.label[language]
              : choice.label[Object.key(choice.label)[0]]
            let o = document.createElement('OPTION')
            o.value = choice.value
            o.classList.add(o.value)
            o.appendChild(document.createTextNode(label))
            domInput.appendChild(o)
          }
          break
        default:
          break
      }
      if (!domInput) { continue }

      if (kCount.key) {
        kCount.key++
      } else {
        kCount.key = 0
      }

      let domLabel = document.createElement('LABEL')
      domInput.setAttribute('name', `${key}[${kCount.key}]`)
      domLabel.appendChild(document.createTextNode(label))
      domLabel.classList.add(data.type, key)

      if (data.readonly) {
        domLabel.classList.add('readonly')
        domInput.setAttribute('disabled', '1')
      } else {
        domInput.setAttribute('tabindex', window.zyxTabIndex++)
      }

      if (languageSelect && window.zyxAcceptLanguages) {
        let ls = document.createElement('SELECT')
        ls.classList.add('languageSelect')
        ls.setAttribute('name', `${key}:lang[${kCount.key}]`)
        for (let l of window.zyxAcceptLanguages) {
          let o = document.createElement('OPTION')
          o.classList.add(l[0])
          o.value = l[0]
          o.appendChild(document.createTextNode(l[1]))
          ls.appendChild(o)
        }
        domLabel.appendChild(ls)
      }

      domLabel.appendChild(domInput)
      fs.appendChild(domLabel)
    }
    return fs
  }

  has (name, idx = null) {
    if (idx === null && this.data[name]) { return true }
    if (idx && this.data[name] && this.data[name][idx]) { return true }
    return false
  }

  get (name, idx = null) {
    if (!this.data[name]) { return false }
    return this.data[name].value
  }

  setParam (name, idx, param, value) {
    if (this.has(name)) {
      if (this.data[name].params === undefined) {
        this.data[name].params = {}
      }
      switch (param) {
        default:
          if (this.data[name].params[param] === undefined) {
            this.data[name].params[param] = {}
          }
          this.data[name].params[param][idx] = value
          break
      }
    }
  }

  reset (name, idx) {
    if (!this.has(name, idx)) { return false }
    this.set(name, idx, null)
  }

  set (name, idx, value) {
    if (!this.has(name)) { return false }
    if (this.data[name].value === null) {
      this.data[name].value = {}
    }
    switch (this.data[name].type) {
      case 'enum':
        break
      case 'string':
      case 'text':
        this.data[name].value[idx] = value
        break
      case 'number':
        let num = parseFloat(value)
        if (isNaN(num)) { return false }

        /* we want only integer */
        if (Math.trunc(num) === num) {
          this.data[name].value[idx] = num
        } else {
          /* max 2 mia */
          const siprefix = ['da', 'h', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y']
          for (let i = 0; i < siprefix.length; i++) {
            let e = 0
            switch (siprefix[i]) {
              case 'da': e = Math.pow(10, 1); break
              case 'h': e = Math.pow(10, 2); break
              case 'k': e = Math.pow(10, 3); break
              case 'M': e = Math.pow(10, 6); break
              case 'G': e = Math.pow(10, 9); break
              case 'T': e = Math.pow(10, 12); break
              case 'P': e = Math.pow(10, 15); break
              case 'E': e = Math.pow(10, 18); break
              case 'Z': e = Math.pow(10, 21); break
              case 'Y': e = Math.pow(10, 24); break
            }
            if (Math.trunc(value * e) === value * e) {
              this.data[name].value[idx] = value * e
              this.setParam(name, idx, 'siprefix', siprefix[i])
              break
            }
          }
        }
        break
    }
  }
}
