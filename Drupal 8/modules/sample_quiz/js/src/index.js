import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import App from './App';
import * as serviceWorker from './serviceWorker';

for (let item in drupalSettings.sample_quiz_node_ids) {
  console.log(item);
  ReactDOM.render(<App nid={item} />, document.getElementById('react_quiz_' + item));
}

// If you want your app to work offline and load faster, you can change
// unregister() to register() below. Note this comes with some pitfalls.
// Learn more about service workers: http://bit.ly/CRA-PWA
serviceWorker.unregister();
